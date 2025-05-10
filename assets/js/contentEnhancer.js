// Intended to be a mirror of the server-side content enhancer
// in app/ContentEnhancer.php
// i.e. the same enhancements are applied to the content during client-side rendering

export default class ContentEnhancer {
    /**
     * Applies available enhancements to the content
     */
    enhanceContent(element) {
        if (element.innerHTML.trim() === '') {
            return;
        }
        
        this.#removeStyleTags(element);
        this.#makeLayoutTablesResponsive(element);
        this.#handleInlineStyles(element);
        this.#removeDivTags(element);
    }

    /**
     * Makes layout tables responsive
     */
    #makeLayoutTablesResponsive(element) {
        const tables = element.querySelectorAll('table');
        tables.forEach(table => {
            const isLayoutTable = this.#isLayoutTable(table);
            if (!isLayoutTable) return;

            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                row.style.display = 'flex';
                row.style.flexWrap = 'wrap';
                row.style.alignItems = 'center';

                // Remove fixed height if set
                if (row.style.height) {
                    row.style.height = null;
                }

                row.setAttribute('data-keep-style', 'true');

                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    if (cell.hasAttribute('width')) {
                        // Convert width to flex basis
                        let basis = cell.getAttribute('width');
                        basis = /^\d+$/.test(basis) ? basis + 'px' : basis;
                        cell.style.flex = `1 1 ${basis}`;
                    }
                    cell.style.boxSizing = 'border-box';
                    cell.style.minWidth = 'min-content';
                    cell.style.marginBlockEnd = '16px';

                    cell.setAttribute('data-keep-style', 'true');

                    // Handle images
                    const images = cell.querySelectorAll('img');
                    images.forEach(img => {
                        if (img.hasAttribute('data-keep-style')) return;
                        const maxW = img.style.maxWidth ?? 'none';
                        if (maxW !== 'none') {
                            img.style.minWidth = `calc(${maxW} * 0.8)`;
                            img.setAttribute('data-keep-style', 'true');
                        }
                    });
                });
            });
        });
    }

    /**
     * Checks if a table has border width set on any of its cells
     * If it does not, we consider the table a layout table
     */
    #isLayoutTable(table) {
        const tableBorderProperties = ['border-top-width', 'border-bottom-width', 'border-left-width', 'border-right-width'];
        const cells = table.querySelectorAll('td');
        for (const cell of cells) {
            const borderWidth = cell.style['border-width'] ?? cell.style['border-width-top'] ?? cell.style['border-width-bottom'] ?? cell.style['border-width-left'] ?? cell.style['border-width-right'];
            if (borderWidth && parseFloat(borderWidth) > 0) {
                return false;
            }

            for (const property of tableBorderProperties) {
                if (cell.style[property] && parseFloat(cell.style[property]) > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Removes all <style> tags from the document
     */
    #removeStyleTags(element) {
        const styleElements = element.querySelectorAll('style');
        styleElements.forEach(el => el.remove());
    }

    /**
     * Handles inline styles:
     * 1. Preserves styles on elements that should keep styling (like images)
     * 2. Removes all other inline style attributes
     */
    #handleInlineStyles(element) {
        // Elements that should keep their style attributes
        const preserveStylesFor = ['img'];

        // Build selector for elements that shouldn't keep their styles
        let selector = '*';
        preserveStylesFor.forEach(tag => {
            selector += `:not(${tag})`;
        });
        selector += '[style]:not([data-keep-style])';
        
        const elementsToRemoveStyle = element.querySelectorAll(selector);
        elementsToRemoveStyle.forEach(el => el.removeAttribute('style'));

        // Check if the root element itself needs style removed
        const isPreservedTag = preserveStylesFor.includes(element.tagName.toLowerCase());
        if (!isPreservedTag && element.hasAttribute('style') && !element.hasAttribute('data-keep-style')) {
            element.removeAttribute('style');
        }
    }

    /**
     * Removes all <div> tags while preserving their content
     */
    #removeDivTags(element) {
        let divElement;
        while ((divElement = element.querySelector('div'))) {
            const parent = divElement.parentNode;
            if (!parent) {
                divElement.remove();
                continue;
            }
            
            // Move all children of the div up one level
            while (divElement.firstChild) {
                parent.insertBefore(divElement.firstChild, divElement);
            }
            
            // Now remove the empty div
            divElement.remove();
        }
    }
}
