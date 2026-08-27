import {ARTICLE_UPDATE_SUBSCRIPTION, PantheonClient, PublishingLevel} from "@pantheon-systems/pcc-sdk-core";

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

        // Preserve server-rendered embeds (oEmbed) before clearing content.
        const savedEmbeds = [...previewContentContainer.querySelectorAll('.cpub-media-embed')];

        previewContentContainer.innerHTML = '';
        previewContentContainer.appendChild(generateHTMLFromJSON(JSON.parse(update.data.article.content), null, savedEmbeds));
    },
});

function generateHTMLFromJSON(json, parentElement = null, savedEmbeds = []) {
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

        // Re-use the server-rendered embed if available, otherwise skip.
        if (tag === 'component' || tag === 'pcc-component') {
            const embed = savedEmbeds.shift();
            if (embed) {
                parent.appendChild(embed);
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

