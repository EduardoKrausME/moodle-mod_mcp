// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Small, dependency-free SDK running inside sandbox="allow-scripts".
 * The trusted parent owns AJAX, session, sesskey and authorization checks.
 */
(function(window) {
    "use strict";

    console.log("[mod_mcp/sdk] Início: JavaScript do SDK está executando.");
    console.log("[mod_mcp/sdk] Ambiente:", {
        embedded: window.parent !== window,
        readyState: window.document ? window.document.readyState : "sem document"
    });

    if (Object.prototype.hasOwnProperty.call(window, "MoodleMCP")) {
        console.log("[mod_mcp/sdk] SDK já inicializado: evitando execução duplicada.");
        return;
    }

    const pending = new Map();
    let sequence = 0;
    let requestResize = null;

    console.log("[mod_mcp/sdk] Registrando listener de mensagens do Moodle.");
    window.addEventListener("message", function(event) {
        if (event.source !== window.parent) {
            console.log("[mod_mcp/sdk] Mensagem ignorada: origem não é o iframe pai.");
            return;
        }
        const msg = event.data;
        if (!msg || msg.channel !== "mod_mcp") {
            return;
        }
        console.log("[mod_mcp/sdk] Mensagem recebida do parent, tipo:", msg.type);
        if (msg.type === "measure") {
            console.log("[mod_mcp/sdk] Parent solicitou nova medição de altura.");
            if (requestResize) {
                // A lost initial resize must be recoverable even if the measured height is unchanged.
                requestResize(true);
            } else {
                console.log("[mod_mcp/sdk] Medição solicitada antes de iniciar auto-resize.");
            }
            return;
        }
        if (msg.type !== "response" || typeof msg.id !== "string") {
            console.log("[mod_mcp/sdk] Tipo de mensagem não reconhecido:", msg.type);
            return;
        }
        const request = pending.get(msg.id);
        if (!request) {
            console.log("[mod_mcp/sdk] Resposta sem requisição pendente:", msg.id);
            return;
        }
        console.log("[mod_mcp/sdk] Resposta recebida para requisição:", msg.id, "ok:", !!msg.ok);
        pending.delete(msg.id);
        clearTimeout(request.timer);
        if (msg.ok) {
            console.log("[mod_mcp/sdk] Requisição concluída com sucesso:", msg.id);
            request.resolve(msg.result);
        } else {
            console.log("[mod_mcp/sdk] Requisição falhou:", msg.id, "erro:", msg.error || "requestfailed");
            request.reject(new Error(String(msg.error || "requestfailed")));
        }
    });

    /**
     * Dispatch a single operation. This identifier is for matching only;
     * it is not a token and is never used as proof of authorization.
     *
     * @param {string} action Allowlisted Moodle action.
     * @param {Object} payload Optional argument object.
     * @returns {Promise<Object>} Validated server result.
     */
    const request = function(action, payload = {}) {
        return new Promise(function(resolve, reject) {
            if (window.parent === window) {
                console.log("[mod_mcp/sdk] Não foi possível enviar a requisição: execução fora do iframe.");
                reject(new Error("MoodleMCP requires an embedded Moodle activity"));
                return;
            }
            const id = "mcp-" + (++sequence).toString(36) + "-" +
                Math.random().toString(36).slice(2, 12);
            const timer = setTimeout(function() {
                console.log("[mod_mcp/sdk] Timeout da requisição:", action, id);
                pending.delete(id);
                reject(new Error("MoodleMCP request timed out"));
            }, 20000);
            pending.set(id, {resolve: resolve, reject: reject, timer: timer});
            console.log("[mod_mcp/sdk] Enviando requisição ao Moodle:", action, "id:", id);
            window.parent.postMessage({
                channel: "mod_mcp", type: "request", id: id,
                action: action, payload: payload
            }, "*");
        });
    };

    const api = {
        activity: Object.freeze({getContext: () => request("activity.getContext")}),
        course: Object.freeze({
            getActivities: () => request("course.getActivities"),
            getNavigation: () => request("course.getNavigation")
        }),
        completion: Object.freeze({
            get: () => request("completion.get"),
            complete: () => request("completion.complete")
        }),
        progress: Object.freeze({
            load: () => request("progress.load"),
            save: (state) => request("progress.save", state)
        }),
        attempt: Object.freeze({
            get: () => request("attempt.get"),
            start: () => request("attempt.start"),
            finish: () => request("attempt.finish")
        }),
        questions: Object.freeze({
            get: () => request("questions.get"),
            submit: (responses, finish = false) =>
                request("questions.submit", {responses: responses, finish: finish})
        }),
        tracking: Object.freeze({
            record: (event) => request("tracking.record", event),
            summary: () => request("tracking.summary")
        })
    };
    Object.defineProperty(window, "MoodleMCP", {
        value: Object.freeze(api), writable: false, configurable: false
    });
    console.log("[mod_mcp/sdk] API window.MoodleMCP registrada com sucesso.");

    /**
     * Report the document's actual height without exposing its DOM to Moodle.
     * The parent can only communicate with this sandbox through postMessage.
     */
    const initAutoResize = function() {
        console.log("[mod_mcp/sdk] Inicializando medição automática do iframe.");
        const doc = window.document;
        if (!doc || window.parent === window) {
            console.log("[mod_mcp/sdk] Auto-resize indisponível: documento ausente ou fora de iframe.");
            return;
        }
        console.log("[mod_mcp/sdk] Documento encontrado, readyState:", doc.readyState);

        let queued = false;
        let lastheight = 0;
        const measure = function() {
            console.log("[mod_mcp/sdk] Executando medição de altura do conteúdo.");
            queued = false;
            const root = doc.documentElement;
            const body = doc.body;
            if (!root) {
                console.log("[mod_mcp/sdk] Medição ignorada: documentElement indisponível.");
                return;
            }
            // html.scrollHeight cannot be used as a minimum: it includes the iframe viewport
            // itself, so a previously tall iframe would otherwise never become shorter.
            // Only count html.scrollHeight when it exceeds the current viewport.
            const viewportHeight = window.innerHeight || root.clientHeight || 0;
            const htmlOverflowHeight = root.scrollHeight > viewportHeight ?
                root.scrollHeight : 0;
            const height = Math.ceil(Math.max(
                root.offsetHeight, body ? body.scrollHeight : 0,
                body ? body.offsetHeight : 0, htmlOverflowHeight
            ));
            console.log("[mod_mcp/sdk] Alturas medidas:", {
                htmlScroll: root.scrollHeight,
                htmlOffset: root.offsetHeight,
                bodyScroll: body ? body.scrollHeight : null,
                bodyOffset: body ? body.offsetHeight : null,
                viewport: viewportHeight,
                htmlOverflow: htmlOverflowHeight,
                calculated: height,
                previous: lastheight
            });
            if (!Number.isFinite(height) || height <= 0) {
                console.log("[mod_mcp/sdk] Medição inválida, ignorando altura:", height);
                return;
            }
            if (height === lastheight) {
                console.log("[mod_mcp/sdk] Altura sem alterações, postMessage dispensado.");
                return;
            }
            lastheight = height;
            console.log("[mod_mcp/sdk] Enviando nova altura ao parent:", height, "px");
            window.parent.postMessage({channel: "mod_mcp", type: "resize", height: height}, "*");
        };
        const schedule = function(force = false) {
            if (force) {
                // A parent handshake must receive a fresh height even if we sent it earlier.
                lastheight = 0;
                console.log("[mod_mcp/sdk] Medição forçada: reenviando altura atual ao parent.");
            }
            if (queued) {
                console.log("[mod_mcp/sdk] Medição já agendada, ignorando agendamento duplicado.");
                return;
            }
            queued = true;
            console.log("[mod_mcp/sdk] Agendando nova medição de altura.");
            if (typeof window.requestAnimationFrame === "function") {
                window.requestAnimationFrame(measure);
            } else {
                console.log("[mod_mcp/sdk] requestAnimationFrame indisponível, usando setTimeout.");
                window.setTimeout(measure, 0);
            }
        };
        requestResize = schedule;
        console.log("[mod_mcp/sdk] Função de medição registrada.");

        const observe = function() {
            console.log("[mod_mcp/sdk] Preparando observers de mudanças no conteúdo.");
            if (typeof window.ResizeObserver === "function") {
                const observer = new window.ResizeObserver(function() {
                    console.log("[mod_mcp/sdk] ResizeObserver detectou mudança de dimensões.");
                    schedule();
                });
                observer.observe(doc.documentElement);
                if (doc.body) {
                    observer.observe(doc.body);
                }
                console.log("[mod_mcp/sdk] ResizeObserver registrado em html/body.");
            } else {
                console.log("[mod_mcp/sdk] ResizeObserver não suportado neste navegador.");
            }
            if (typeof window.MutationObserver === "function") {
                const observer = new window.MutationObserver(function() {
                    console.log("[mod_mcp/sdk] MutationObserver detectou alteração no DOM.");
                    schedule();
                });
                observer.observe(doc.documentElement, {
                    attributes: true, childList: true, characterData: true, subtree: true
                });
                console.log("[mod_mcp/sdk] MutationObserver registrado no documento.");
            } else {
                console.log("[mod_mcp/sdk] MutationObserver não suportado neste navegador.");
            }
            console.log("[mod_mcp/sdk] Solicitando medição inicial.");
            schedule();
        };
        if (doc.readyState === "loading") {
            console.log("[mod_mcp/sdk] Aguardando DOMContentLoaded para registrar observers.");
            doc.addEventListener("DOMContentLoaded", observe, {once: true});
        } else {
            console.log("[mod_mcp/sdk] DOM já disponível: registrando observers imediatamente.");
            observe();
        }
        doc.addEventListener("load", function() {
            console.log("[mod_mcp/sdk] Evento load no documento; recalculando altura.");
            schedule();
        }, true);
        window.addEventListener("load", function() {
            console.log("[mod_mcp/sdk] Evento window.load; recalculando altura.");
            schedule();
        });
        window.addEventListener("resize", function() {
            console.log("[mod_mcp/sdk] Evento window.resize; recalculando altura.");
            schedule();
        });
        if (doc.fonts && doc.fonts.ready) {
            console.log("[mod_mcp/sdk] Aguardando carregamento das fontes.");
            doc.fonts.ready.then(function() {
                console.log("[mod_mcp/sdk] Fontes carregadas; recalculando altura.");
                schedule();
            });
        }
        console.log("[mod_mcp/sdk] Auto-resize inicializado com sucesso.");
    };

    initAutoResize();
    if (window.parent !== window && window.document) {
        console.log("[mod_mcp/sdk] Enviando sinal ready ao bridge do Moodle.");
        window.parent.postMessage({channel: "mod_mcp", type: "ready"}, "*");
    }
    console.log("[mod_mcp/sdk] Fim da inicialização do SDK.");
})(window);
