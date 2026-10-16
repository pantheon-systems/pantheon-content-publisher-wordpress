import {ARTICLE_UPDATE_SUBSCRIPTION, PantheonClient, PublishingLevel} from "@pantheon-systems/pcc-sdk-core";

// Must match SmartComponents::PREVIEW_WRAPPER_CLASS in PHP.
const RENDERED_COMPONENT_SELECTOR = '.cpub-smart-component';
// Markup produced before the generic wrapper existed (Media Embed only).
const LEGACY_RENDERED_COMPONENT_SELECTOR = '.cpub-media-embed';

const url = new URL(window.location.href);
const params = new URLSearchParams(url.search);
const siteId = params.get('site_id') || window.PCCFront.site_id;
const documentId = params.get('document_id');
const pccGrant = params.get('pccGrant');
const versionId = params.get('versionId');
const publishingLevel = params.get('publishing_level') || PublishingLevel.REALTIME;

const pantheonClient = new PantheonClient({
    siteId: siteId,
    pccGrant: pccGrant,
});

const subscriptionVariables = {
    id: documentId,
    contentType: "TREE_PANTHEON_V2",
    publishingLevel,
};

if (versionId) {
    subscriptionVariables.versionId = versionId;
}

const observable = pantheonClient.apolloClient.subscribe({
    query: ARTICLE_UPDATE_SUBSCRIPTION,
    variables: subscriptionVariables,
});

observable.subscribe({
    next: (update) => {
        if (!update.data) return;
        const article = update.data.article;
        // Bail if current article is not equal to one in session
        // @TODO it's already checked and register above and needs to be revisited again before removing the following code
        if (documentId !== article.id) {
            return;
        }

        const entryTitle = document.querySelector('h1');
        entryTitle.innerHTML = article.title;

        var previewContentContainer = document.getElementById('pcc-content-preview');
        const tree = JSON.parse(update.data.article.content);

        // Smart components are rendered server-side (PHP renderers, oEmbed,
        // third-party plugins) and cannot be rebuilt here. Keep every
        // server-rendered component so it can be re-inserted where the
        // updated tree has a component node.
        const renderedComponents = collectRenderedComponents(previewContentContainer, tree);

        previewContentContainer.innerHTML = '';
        previewContentContainer.appendChild(generateHTMLFromJSON(tree, null, renderedComponents));
    },
});

/**
 * Collect the server-rendered smart components currently in the preview
 * container and return a store that hands them back per component node.
 *
 * Components are matched by id (data-cpub-component-id vs the tree node's
 * id). Rendered components without an id are handed out positionally, in
 * document order, for markup that predates ids. A rendered component whose
 * id no longer appears in the tree was removed from the document and is
 * dropped.
 */
function collectRenderedComponents(container, tree) {
    let elements = [...container.querySelectorAll(RENDERED_COMPONENT_SELECTOR)];
    if (!elements.length) {
        elements = [...container.querySelectorAll(LEGACY_RENDERED_COMPONENT_SELECTOR)];
    }

    const treeIds = new Set();
    const walk = (node) => {
        if (!node || typeof node !== 'object') return;
        if (isComponentNode(node) && componentNodeId(node)) {
            treeIds.add(componentNodeId(node));
        }
        (node.children || []).forEach(walk);
    };
    walk(tree);

    const byId = new Map();
    const positional = [];
    const treeHasIds = treeIds.size > 0;
    elements.forEach((element) => {
        const id = element.getAttribute('data-cpub-component-id');
        if (!id || !treeHasIds) {
            positional.push(element);
        } else if (treeIds.has(id)) {
            byId.set(id, element);
        }
    });

    return {
        take(id) {
            if (id && byId.has(id)) {
                const element = byId.get(id);
                byId.delete(id);
                return element;
            }
            return positional.shift() || null;
        },
    };
}

function isComponentNode(node) {
    return node.tag === 'component' || node.tag === 'pcc-component';
}

function componentNodeId(node) {
    return node.id || (node.attrs && node.attrs.id) || null;
}

function generateHTMLFromJSON(json, parentElement = null, renderedComponents = null) {
    const createElement = (tag, attrs = {}, styles = {}, content = '') => {
        if (undefined === tag) {
            tag = 'div';
        }
        const element = document.createElement(tag);

        // Set attributes
        for (const [key, value] of Object.entries(attrs)) {
            element.setAttribute(key, value);
        }

        // Set styles
        if (Array.isArray(styles)) {
            styles.forEach(style => {
                const [key, value] = style.split(':').map(s => s.trim());
                element.style[key] = value;
            });
        } else if (typeof styles === 'object') {
            for (const [key, value] of Object.entries(styles)) {
                element.style[key] = value;
            }
        }

        // Set content
        if (content !== null) {
            element.innerHTML = content;
        }

        return element;
    };

    const processNode = (node, parent, uniqueClass) => {
        const {tag, data, children, style, attrs} = node;

        // Re-insert the server-rendered component if there is one. A component
        // added since the page was rendered has nothing to show until reload.
        if (isComponentNode(node)) {
            const rendered = renderedComponents ? renderedComponents.take(componentNodeId(node)) : null;
            if (rendered) {
                parent.appendChild(rendered);
            }
            return;
        }

        const hasChildren = children && children.length;
        const hasData = data !== null && data !== '';
        if (!hasChildren && !hasData && (attrs === undefined || Object.keys(attrs).length === 0)) {
            return;
        }

        // Scope styles if the tag is 'style'
        if (tag === 'style' && data) {
            const scopedData = `.${uniqueClass} ${data}`;
            const element = createElement(tag, attrs, style || [], scopedData);
            parent.appendChild(element);
            return;
        }

        const element = createElement(tag, attrs, style || [], data !== null ? data : '');

        if (hasChildren) {
            children.forEach(child => processNode(child, element, uniqueClass));
        }

        parent.appendChild(element);
    };

    // Create a container if parentElement is not provided
    const container = parentElement || document.createElement('div');

    // Generate a unique class name for scoping
    const uniqueClass = 'scoped-' + Math.random().toString(36).substr(2, 9);
    container.classList.add(uniqueClass);

    processNode(json, container, uniqueClass);

    return container;
}

