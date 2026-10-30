import { beforeEach, describe, expect, it } from "vitest";
import { EMBED_SELECTOR, SLOT_ATTRIBUTE, createSlot, updatePreview } from "../../assets/src/js/live-preview-dom";

const embed = (id) =>
    `<div class="cpub-media-embed" data-id="${id}"><iframe src="https://www.youtube.com/embed/${id}"></iframe></div>`;

// Builds a tree shaped like generateHTMLFromJSON() output; <x-slot></x-slot> marks a component.
function freshTree(html, scopedClass = "scoped-next") {
    const tree = document.createElement("div");
    tree.classList.add(scopedClass);
    tree.innerHTML = html;
    for (const placeholder of tree.querySelectorAll("x-slot")) {
        placeholder.replaceWith(createSlot());
    }
    return tree;
}

// An iframe taken out of the page and put back gets a new window, which is the reload behind the flicker.
// Returns the embeds whose iframe was reloaded since the call.
function watchReloads(container) {
    const before = [...container.querySelectorAll(`${EMBED_SELECTOR} iframe`)].map((frame) => [frame, frame.contentWindow]);
    return () => before.filter(([frame, win]) => frame.contentWindow !== win).map(([frame]) => frame.closest(EMBED_SELECTOR));
}

const embedIds = (container) => [...container.querySelectorAll(EMBED_SELECTOR)].map((el) => el.dataset.id);

describe("updatePreview", () => {
    let container;

    beforeEach(() => {
        document.body.innerHTML = '<div id="pcc-content-preview"></div>';
        container = document.getElementById("pcc-content-preview");
    });

    describe("first update after the server render", () => {
        it("moves each server-rendered embed into its slot, in document order", () => {
            container.innerHTML = `<p>Intro</p>${embed("a")}<p>Middle</p>${embed("b")}`;
            const [a, b] = container.querySelectorAll(EMBED_SELECTOR);

            updatePreview(container, freshTree("<p>Intro</p><x-slot></x-slot><p>Middle</p><x-slot></x-slot>"));

            const kept = container.querySelectorAll(EMBED_SELECTOR);
            expect(kept[0]).toBe(a);
            expect(kept[1]).toBe(b);
            expect(container.textContent).toBe("IntroMiddle");
            expect(container.querySelector(`[${SLOT_ATTRIBUTE}]`)).toBeNull();
        });
    });

    describe("later updates", () => {
        beforeEach(() => {
            container.innerHTML = `<p>Intro</p>${embed("a")}`;
            updatePreview(container, freshTree("<p>Intro</p><x-slot></x-slot>", "scoped-first"));
        });

        it("keeps the embed in the page when the text around it changes", () => {
            const a = container.querySelector(EMBED_SELECTOR);
            const reloaded = watchReloads(container);

            updatePreview(container, freshTree("<p>Intro, edited</p><x-slot></x-slot><p>Outro</p>"));

            expect(reloaded()).toEqual([]);
            expect(container.querySelector(EMBED_SELECTOR)).toBe(a);
            expect(container.textContent).toBe("Intro, editedOutro");
        });

        it("keeps the embed in the page when a paragraph is added before it", () => {
            const a = container.querySelector(EMBED_SELECTOR);
            const reloaded = watchReloads(container);

            updatePreview(container, freshTree("<p>Intro</p><h2>New heading</h2><x-slot></x-slot>"));

            expect(reloaded()).toEqual([]);
            const scoped = container.firstElementChild;
            expect([...scoped.children].map((el) => el.tagName)).toEqual(["P", "H2", "DIV"]);
            expect(scoped.lastElementChild).toBe(a);
        });

        it("keeps the embed in the page when the paragraph before it is deleted", () => {
            const a = container.querySelector(EMBED_SELECTOR);
            const reloaded = watchReloads(container);

            updatePreview(container, freshTree("<x-slot></x-slot>"));

            expect(reloaded()).toEqual([]);
            expect(container.firstElementChild.firstChild).toBe(a);
            expect(container.textContent).toBe("");
        });

        it("applies the new scoped class to the kept wrapper", () => {
            updatePreview(container, freshTree("<p>Intro</p><x-slot></x-slot>", "scoped-second"));

            expect(container.firstElementChild.className).toBe("scoped-second");
        });

        it("drops the embed when its component is removed", () => {
            updatePreview(container, freshTree("<p>Intro</p>"));

            expect(container.querySelector(EMBED_SELECTOR)).toBeNull();
            expect(container.textContent).toBe("Intro");
        });

        it("leaves no empty slot for a component added since the page was rendered", () => {
            updatePreview(container, freshTree("<p>Intro</p><x-slot></x-slot><x-slot></x-slot>"));

            expect(embedIds(container)).toEqual(["a"]);
            expect(container.querySelector(`[${SLOT_ATTRIBUTE}]`)).toBeNull();
        });
    });

    it("keeps two embeds in place and in order across an update", () => {
        container.innerHTML = `${embed("a")}<p>Between</p>${embed("b")}`;
        updatePreview(container, freshTree("<x-slot></x-slot><p>Between</p><x-slot></x-slot>"));
        const reloaded = watchReloads(container);

        updatePreview(container, freshTree("<x-slot></x-slot><p>Between, edited</p><x-slot></x-slot>"));

        expect(reloaded()).toEqual([]);
        expect(embedIds(container)).toEqual(["a", "b"]);
    });

    it("keeps an embed in place inside a nested block", () => {
        container.innerHTML = embed("a");
        updatePreview(container, freshTree("<div><p>Text</p><x-slot></x-slot></div>"));
        const a = container.querySelector(EMBED_SELECTOR);
        const reloaded = watchReloads(container);

        updatePreview(container, freshTree("<div><p>Text, edited</p><x-slot></x-slot></div>"));

        expect(reloaded()).toEqual([]);
        expect(container.querySelector("div > div > div").lastElementChild).toBe(a);
        expect(container.textContent).toBe("Text, edited");
    });

    it("moves an embed once when its component moves to another block", () => {
        container.innerHTML = embed("a");
        updatePreview(container, freshTree("<x-slot></x-slot><div><p>Box</p></div>"));
        const a = container.querySelector(EMBED_SELECTOR);

        updatePreview(container, freshTree("<p>Top</p><div><p>Box</p><x-slot></x-slot></div>"));

        expect(container.querySelector("div > div > div > div.cpub-media-embed")).toBe(a);
        expect(embedIds(container)).toEqual(["a"]);
    });
});
