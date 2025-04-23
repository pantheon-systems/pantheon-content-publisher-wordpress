import {ARTICLE_UPDATE_SUBSCRIPTION, PantheonClient, PublishingLevel} from "@pantheon-systems/pcc-sdk-core";

const url = new URL(window.location.href);
const params = new URLSearchParams(url.search);
const siteId = params.get('site_id') || window.PCCFront.site_id;
const documentId = params.get('document_id');
const pccGrant = params.get('pccGrant');

const pantheonClient = new PantheonClient({
    siteId: siteId,
    pccGrant: pccGrant,
});

const observable = pantheonClient.apolloClient.subscribe({
    query: ARTICLE_UPDATE_SUBSCRIPTION,
    variables: {
        id: documentId,
        contentType: "TREE_PANTHEON_V2",
        publishingLevel: PublishingLevel.REALTIME,
    },
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
        previewContentContainer.innerHTML = '';
        previewContentContainer.appendChild(generateHTMLFromJSON(JSON.parse(update.data.article.content)));
    },
});

function generateHTMLFromJSON(json, parentElement = null) {
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
