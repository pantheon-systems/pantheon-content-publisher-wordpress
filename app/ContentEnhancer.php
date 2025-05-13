<?php

namespace Pantheon\ContentPublisher;

use DiDom\Document;
use DiDom\Query;
use DiDom\Element;

class ContentEnhancer
{
	/**
	 * Applies available enhancements to the content
	 */
	public function enhanceContent(string $html): string
	{
		if (empty(trim($html))) {
			return $html;
		}
		
		$doc = new Document($html);
		
		$this->removeStyleTags($doc);
		$this->makeLayoutTablesResponsive($doc);
		$this->handleInlineStyles($doc);
		$this->removeDivTags($doc);

		return $doc->html();
	}

	/**
	 * Makes layout tables responsive
	 */
	public function makeLayoutTablesResponsive(Document $doc): void
	{
		$tables = $doc->find('table');
		foreach ($tables as $table) {
			$isLayoutTable = $this->isLayoutTable($table);
			if (!$isLayoutTable) {
				continue;
			}

			$rows = $table->findInDocument('tr');
			foreach ($rows as $row) {
				$rowStyle = $this->cssToArray($row->attr('style'));
				$rowStyle['display']     = 'flex';
				$rowStyle['flex-wrap']   = 'wrap';
				$rowStyle['align-items'] = 'center';

				// Remove fixed height if set
				if (isset($rowStyle['height'])) {
					unset($rowStyle['height']);
				}

				$row->setAttribute('style', $this->arrayToCss($rowStyle));
				$row->setAttribute('data-keep-style', 'true');

				$cells = $row->findInDocument('td');
				foreach ($cells as $cell) {
					$cellStyle = $this->cssToArray($cell->attr('style'));

					// Convert width to flex basis
					$basis = $cell->hasAttribute('width') ? 
						$cell->attr('width') : 
						// If no width is set, default to equal width columns
						(100 / count($cells)) . '%';
					$basis = ctype_digit($basis) ? $basis . 'px' : $basis;
					$cellStyle['flex'] = "1 1 $basis";
					
					$cellStyle['box-sizing']        = 'border-box';
					$cellStyle['min-width']         = 'min-content';
					$cellStyle['margin-block-end']  = '16px';
					$cell->setAttribute('style', $this->arrayToCss($cellStyle));
					$cell->setAttribute('data-keep-style', 'true');

					// Handle images
					$images = $cell->findInDocument('img');
					foreach ($images as $img) {
						if ($img->hasAttribute('data-keep-style')) {
							continue;
						}
						$imgStyle = $this->cssToArray($img->attr('style'));
						$maxW = $imgStyle['max-width'] ?? 'none';
						if ($maxW !== 'none') {
							$imgStyle['min-width'] = "calc($maxW * 0.8)";
							$img->setAttribute('style', $this->arrayToCss($imgStyle));
							$img->setAttribute('data-keep-style', 'true');
						}
					}
				}
			}
		}
	}

	/**
	 * Checks if a table has border width set on any of its cells
	 * If it does not, we consider the table a layout table
	 */
	private function isLayoutTable(Element $table): bool
	{
		$borderProps = ['border-top-width', 'border-bottom-width', 'border-left-width', 'border-right-width'];
		foreach ($table->find('td') as $td) {
			$style = $this->cssToArray($td->attr('style'));

			foreach ($borderProps as $property) {
				if (isset($style[$property]) && floatval($style[$property]) > 0) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Removes all <style> tags from the document
	 */
	private function removeStyleTags(Document $doc): void
	{
		foreach ($doc->find('style') as $styleElement) {
			$styleElement->remove();
		}
	}

	/**
	 * Handles inline styles:
	 * 1. Preserves styles on elements that should keep styling (like images)
	 * 2. Removes all other inline style attributes
	 */
	private function handleInlineStyles(Document $doc): void
	{
		// Elements that should keep their style attributes
		$preserveStylesFor = ['img'];

		// Find all elements with style attributes that are not in our preserve list
		$excludeSelectors = array_map(function ($tagName) {
			return "self::{$tagName}";
		}, $preserveStylesFor);

		// Build the XPath selector
		// Also ignores elements that have a data-keep-style attribute
		$selector = '//*[not(' . implode(' or ', $excludeSelectors) . ') and @style and not(@data-keep-style)]';
				
		foreach ($doc->find($selector, Query::TYPE_XPATH) as $element) {
			$element->removeAttribute('style');
		}
	}

	/**
	 * Removes all <div> tags while preserving their content
	 */
	private function removeDivTags(Document $doc): void
	{
		// Keep processing divs until no more are found
		while ($div = $doc->first('div')) {
			$domNode = $div->getNode();
			$parent = $domNode->parentNode;
			
			// Move all children of the div up one level
			while ($domNode->firstChild) {
				$child = $domNode->firstChild;
				$domNode->removeChild($child);
				$parent->insertBefore($child, $domNode);
			}
			
			// Now remove the empty div
			$parent->removeChild($domNode);
		}
	}

	/**
	 * Converts CSS string to associative array
	 */
	private function cssToArray(?string $style): array
	{
		$out = [];
		foreach (explode(';', $style ?? '') as $decl) {
			if (str_contains($decl, ':')) {
				[$propKey, $propVal] = array_map('trim', explode(':', $decl, 2));
				if ($propKey !== '') {
					$out[strtolower($propKey)] = $propVal;
				}
			}
		}
		return $out;
	}

	/**
	 * Converts associative array to CSS string
	 */
	private function arrayToCss(array $rules): string
	{
		$parts = [];
		foreach ($rules as $propKey => $propVal) {
			$parts[] = "$propKey:$propVal";
		}
		return implode(';', $parts);
	}
}
