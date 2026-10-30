/**
 * Updates the real-time preview in place instead of rebuilding it.
 *
 * An iframe that is removed from the page and inserted again is reloaded by
 * the browser, so rebuilding the preview on every update made embedded videos
 * flicker. This keeps each rendered component where it is and only replaces
 * the content around it, matching components by id when the tree provides
 * one (see SmartComponents::wrapForPreview() in PHP) and falling back to
 * document order for markup rendered before ids were wrapped in.
 */

// Marker wrapped around each rendered component. Must match
// SmartComponents::PREVIEW_WRAPPER_CLASS in PHP.
export const COMPONENT_SELECTOR = '.cpub-smart-component';
// Markup produced before the generic wrapper existed (Media Embed only).
export const EMBED_SELECTOR = '.cpub-media-embed';
export const SLOT_ATTRIBUTE = 'data-cpub-slot';
export const ID_ATTRIBUTE = 'data-cpub-component-id';

const isElement = (node) => node && node.nodeType === Node.ELEMENT_NODE;
const isSlot = (node) => isElement(node) && node.hasAttribute(SLOT_ATTRIBUTE);
const isPreserved = (node, preservedSet) => isElement(node) && preservedSet.has(node);
const sameKind = (a, b) =>
    a.nodeType === b.nodeType && (!isElement(a) || a.tagName === b.tagName);

/**
 * Create the empty element that marks where a component goes in a rebuilt tree.
 *
 * @param {string|null} [id] Component id from the tree node, if any.
 */
export function createSlot(id) {
    const slot = document.createElement('div');
    slot.setAttribute(SLOT_ATTRIBUTE, '');
    if (id) {
        slot.setAttribute(ID_ATTRIBUTE, id);
    }
    return slot;
}

/**
 * Collect the elements in `container` that must not be detached: the server-
 * rendered components from the last render. Prefers the generic wrapper; a
 * container with no wrapped components falls back to the legacy Media Embed
 * markup it was rendered with before the wrapper existed. The two are never
 * mixed, since a wrapped Media Embed contains its own `.cpub-media-embed`
 * element as a descendant, not a sibling.
 */
function collectPreservedElements(container) {
    const wrapped = [...container.querySelectorAll(COMPONENT_SELECTOR)];
    if (wrapped.length) {
        return wrapped;
    }
    return [...container.querySelectorAll(EMBED_SELECTOR)];
}

function containsSlot(node) {
    return isElement(node) && (isSlot(node) || node.querySelector(`[${SLOT_ATTRIBUTE}]`) !== null);
}

function holdsPreservedOrSlot(node, preservedSet) {
    if (!isElement(node)) {
        return false;
    }
    if (containsSlot(node)) {
        return true;
    }
    for (const preserved of preservedSet) {
        if (node.contains(preserved)) {
            return true;
        }
    }
    return false;
}

/**
 * Whether `node` is the preserved element a slot wants: matched by id when
 * the tree carries ids, otherwise any preserved element in document order.
 */
function slotMatches(node, wantedId, treeHasIds, preservedSet) {
    if (!isPreserved(node, preservedSet)) {
        return false;
    }
    if (!treeHasIds) {
        return true;
    }
    return wantedId ? node.getAttribute(ID_ATTRIBUTE) === wantedId : !node.getAttribute(ID_ATTRIBUTE);
}

function syncAttributes(target, source) {
    for (const { name } of [...target.attributes]) {
        if (!source.hasAttribute(name)) {
            target.removeAttribute(name);
        }
    }
    for (const { name, value } of [...source.attributes]) {
        if (target.getAttribute(name) !== value) {
            target.setAttribute(name, value);
        }
    }
}

/**
 * Make the children of `target` match the children of `source`, keeping
 * preserved elements in place. A slot in `source` is matched with the next
 * preserved element in `target` at the same level that fits its wanted id (if
 * any); preserved elements skipped over are detached and left for
 * fillSlots(). Unmatched slots are left in place for fillSlots() too.
 */
function patchChildren(target, source, preservedSet, treeHasIds) {
    const current = [...target.childNodes];
    const incoming = [...source.childNodes];
    let pointer = 0;

    for (const node of incoming) {
        if (isSlot(node)) {
            const wantedId = node.getAttribute(ID_ATTRIBUTE);
            let next = pointer;
            while (next < current.length && !slotMatches(current[next], wantedId, treeHasIds, preservedSet)) {
                next++;
            }
            if (next < current.length) {
                for (let i = pointer; i < next; i++) {
                    current[i].remove();
                }
                pointer = next + 1;
            } else {
                target.insertBefore(node, current[pointer] || null);
            }
            continue;
        }

        const existing = current[pointer];
        if (existing && !isPreserved(existing, preservedSet) && sameKind(existing, node)) {
            if (!isElement(node)) {
                if (existing.nodeValue !== node.nodeValue) {
                    existing.nodeValue = node.nodeValue;
                }
            } else if (holdsPreservedOrSlot(existing, preservedSet) || containsSlot(node)) {
                syncAttributes(existing, node);
                patchChildren(existing, node, preservedSet, treeHasIds);
            } else if (!existing.isEqualNode(node)) {
                target.replaceChild(node, existing);
            }
            pointer++;
        } else {
            target.insertBefore(node, existing || null);
        }
    }

    for (let i = pointer; i < current.length; i++) {
        current[i].remove();
    }
}

/**
 * Fill the slots left unmatched with preserved elements that patchChildren()
 * detached, matching by id first when the tree carries ids (falling back to
 * document order for id-less slots or elements), then by document order.
 * This moves the element, so it reloads once; it only happens when it cannot
 * stay where it is (first update after the server render, or a component
 * moved to another block). A preserved element whose id no longer appears in
 * any slot was removed from the document and is dropped.
 */
function fillSlots(container, preservedElements, treeHasIds) {
    const displaced = preservedElements.filter((element) => !container.contains(element));

    const byId = new Map();
    const positional = [];
    displaced.forEach((element) => {
        const id = element.getAttribute(ID_ATTRIBUTE);
        if (!id || !treeHasIds) {
            positional.push(element);
        } else {
            byId.set(id, element);
        }
    });

    const take = (wantedId) => {
        if (wantedId && byId.has(wantedId)) {
            const element = byId.get(wantedId);
            byId.delete(wantedId);
            return element;
        }
        return positional.shift() || null;
    };

    for (const slot of container.querySelectorAll(`[${SLOT_ATTRIBUTE}]`)) {
        const element = take(slot.getAttribute(ID_ATTRIBUTE));
        if (element) {
            slot.replaceWith(element);
        } else {
            // A component added since the page was rendered: nothing to show until reload.
            slot.remove();
        }
    }
}

/**
 * Update `container` so its content matches `freshTree`.
 *
 * @param {Element} container The preview container (#pcc-content-preview).
 * @param {Element} freshTree The rebuilt article, with a slot for each component.
 */
export function updatePreview(container, freshTree) {
    const preserved = collectPreservedElements(container);
    const preservedSet = new Set(preserved);
    const wrapper = document.createElement('div');
    wrapper.appendChild(freshTree);
    const treeHasIds = wrapper.querySelector(`[${SLOT_ATTRIBUTE}][${ID_ATTRIBUTE}]`) !== null;
    patchChildren(container, wrapper, preservedSet, treeHasIds);
    fillSlots(container, preserved, treeHasIds);
}
