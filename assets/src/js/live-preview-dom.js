/**
 * Updates the real-time preview in place instead of rebuilding it.
 *
 * An iframe that is removed from the page and inserted again is reloaded by
 * the browser, so rebuilding the preview on every update made embedded videos
 * flicker. This keeps each rendered embed element where it is and only
 * replaces the content around it.
 */

export const EMBED_SELECTOR = '.cpub-media-embed';
export const SLOT_ATTRIBUTE = 'data-cpub-slot';

const isElement = (node) => node && node.nodeType === Node.ELEMENT_NODE;
const isEmbed = (node) => isElement(node) && node.matches(EMBED_SELECTOR);
const isSlot = (node) => isElement(node) && node.hasAttribute(SLOT_ATTRIBUTE);
const holdsEmbedOrSlot = (node) =>
    isElement(node) && node.querySelector(`${EMBED_SELECTOR}, [${SLOT_ATTRIBUTE}]`) !== null;
const sameKind = (a, b) =>
    a.nodeType === b.nodeType && (!isElement(a) || a.tagName === b.tagName);

/**
 * Create the empty element that marks where a component goes in a rebuilt tree.
 */
export function createSlot() {
    const slot = document.createElement('div');
    slot.setAttribute(SLOT_ATTRIBUTE, '');
    return slot;
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
 * existing embed elements in place. A slot in `source` is matched with the
 * next embed in `target` at the same level; unmatched slots are left in place
 * for fillSlots().
 */
function patchChildren(target, source) {
    const current = [...target.childNodes];
    const incoming = [...source.childNodes];
    let pointer = 0;

    for (const node of incoming) {
        if (isSlot(node)) {
            let next = pointer;
            while (next < current.length && !isEmbed(current[next])) {
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
        if (existing && !isEmbed(existing) && sameKind(existing, node)) {
            if (!isElement(node)) {
                if (existing.nodeValue !== node.nodeValue) {
                    existing.nodeValue = node.nodeValue;
                }
            } else if (holdsEmbedOrSlot(existing) || holdsEmbedOrSlot(node)) {
                syncAttributes(existing, node);
                patchChildren(existing, node);
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
 * Fill the slots left unmatched with saved embeds, in document order.
 * This moves the embed, so it reloads once; it only happens when an embed
 * cannot stay where it is (first update after the server render, or a
 * component moved to another block).
 */
function fillSlots(container, savedEmbeds) {
    const unused = savedEmbeds.filter((embed) => !container.contains(embed));
    for (const slot of container.querySelectorAll(`[${SLOT_ATTRIBUTE}]`)) {
        const embed = unused.shift();
        if (embed) {
            slot.replaceWith(embed);
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
    const savedEmbeds = [...container.querySelectorAll(EMBED_SELECTOR)];
    const wrapper = document.createElement('div');
    wrapper.appendChild(freshTree);
    patchChildren(container, wrapper);
    fillSlots(container, savedEmbeds);
}
