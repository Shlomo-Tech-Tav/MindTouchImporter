<?php
/**
 * The Content Tools class performs cleanup operations on the
 * currently loaded phpQuery document.
 */
class ContentTools {
	protected $content;
	protected $remove_data = array();
	protected $styles = array();
	public $anchor_https = array();
	public $anchor_names = array();

	/**
	 * Returns the array that contains any data from the removal.
	 * @return array $remove_data Array of information about the removal.
	 */
	public function getRemoveData() {
		return $this->remove_data;
	}

	/**
	 * Builds array of internal links in the content.
	 * @param string $content HTML content to search.
	 */
	public function setInternalLinks($content) {
		// Load the content in phpQuery.
		$content = phpQuery::newDocumentHTML($content);

		// Build array of internal links.
		$this->anchor_https = array();
		foreach ($content->find('a[href]') as $a) {
			$a = pq($a);
			$href = $a->attr('href');
			if (stripos($href, 'http') === false) {
				$this->anchor_https[] = array(
					'href' => $href,
					'text' => $a->text()
				);
			}
		}

		// Build array of names.
		$this->anchor_names = array();
		foreach ($content->find('a[name]') as $a) {
			$a = pq($a);
			$name = $a->attr('name');
			$this->anchor_names[] = array(
				'name' => $name,
				'text' => $a->text()
			);
		}
	}

	/**
	 * Remove the selected CSS and HTML attributes from the content.
	 * @param string $content HTML content to work on.
	 * @param array $remove_items Array of functions to run on the content.
	 * @param array $options Array of options to pass to helper remove functions.
	 * @return string $content Content with the content cleaned up.
	 */
	public function remove($content, $remove_items, $options = array()) {
		$this->content = $content;
		$this->styles = array();

		// Some items can be removed without phpQuery.
		foreach ($remove_items as $remove) {
			switch ($remove) {
				case 'font':
					$this->removeFont($options);
					break;
				case 'tableBody':
					$this->removeTableBodies($options);
					break;
				case 'tableHead':
					$this->removeTableHeads($options);
					break;
			}
		}

		// Load the import in phpQuery.
		$this->content = phpQuery::newDocumentHTML($this->content);

		foreach ($remove_items as $remove) {
			switch ($remove) {
				case 'anchorNames':
					$this->removeAnchorNames($options);
					break;
				case 'backgroundColor':
					$this->removeBackgroundColor($options);
					break;
				case 'emptyDivs':
					$this->removeEmptyDivs($options);
					break;
				case 'emptyParagraphs':
					$this->removeEmptyParagraphs($options);
					break;
				case 'emptySpans':
					$this->removeEmptySpans($options);
					break;
				case 'fontFaces':
					$this->removeFontFaces($options);
					break;
				case 'fontSizes':
					$this->removeFontSizes($options);
					break;
				case 'imageHeights':
					$this->removeImageHeights($options);
					break;
				case 'imageWidths':
					$this->removeImageWidths($options);
					break;
				case 'lang':
					$this->removeLang($options);
					break;
				case 'marginLeft':
					$this->removeMarginLeft($options);
					break;
				case 'noWrap':
					$this->removeNoWrap($options);
					break;
				case 'position':
					$this->removePosition($options);
					break;
				case 'textAlign':
					$this->removeTextAlign($options);
					break;
				case 'textIndent':
					$this->removeTextIndent($options);
					break;
				case 'tableHeights':
					$this->removeTableHeights($options);
					break;
				case 'tablePadding':
					$this->removeTablePadding($options);
					break;
				case 'tableWidths':
					$this->removeTableWidths($options);
					break;
				case 'zindex':
					$this->removeZindex($options);
					break;
			}
		}
		$this->removeStyles($this->styles, $options);
		return $this->content->html();
	}

	/**
	 * Removes anchor names from the content.
	 * @param array $options
	 */
	protected function removeAnchorNames($options) {
		// Get scan type.
		$scan = !empty($options['scan']) ? $options['scan'] : 'easy';

		// Get names to keep.
		$keepers = !empty($options['keepers']) ? $options['keepers'] : array();

		// Deep scan does a full search and may time out.
		if ($scan === 'deep') {
			// Remove the anchor tags, but keep any content inside them.
			$a_i = 0;
			foreach ($this->content->find('a[name]') as $a) {
				// Get DOM data for the anchor.
				$a = pq($a);
				$name = $a->attr('name');

				// Make sure it's not in the keepers.
				if (!in_array($name, $keepers)) {
					$a_html = $a->html();

					// See if the anchor has any descendants.
					if (!empty($a_html)) {
						// It's not empty. Grab any HTML from siblings.
						$parent = $a->parent();
						$parent_html = '';
						foreach ($parent->children() as $sibling) {
							// Get sibling information.
							$sibling = pq($sibling);
							$tagName = $sibling->elements[0]->tagName;
							$sibling_html = $sibling->html();

							// Prepare parent HTML.
							if ($tagName === 'a' && $a_html === $sibling_html) {
								// This is the anchor. Use it's HTML.
								$parent_html .= $a_html;
							} else {
								// This is not the anchor. Build its attributes.
								$sibling_attrs = '';
								foreach ($sibling->elements[0]->attributes as $attr) {
									$sibling_attrs .= ' ' . $attr->nodeName . '=';
									if (strpos($attr->nodeValue, '"') !== false) {
										// Has double quotes. Use single.
										$sibling_attrs .= "'" . $attr->nodeValue . "'";
									} else {
										$sibling_attrs .= '"' . $attr->nodeValue . '"';
									}
								}
								$sibling_html = "<$tagName $sibling_attrs>$sibling_html</$tagName>";
								$parent_html .= $sibling_html;
							}
						}
						$parent->html($parent_html);
						$removed ++;
					} else {
						// Remove the anchor.
						$a->remove();
						$removed ++;
					}
				}
				$a_i ++;
				if ($a_i > 500) {
					break;
				}
			}
		} else {
			$removed = 0;
			foreach ($this->content->find('a[name]') as $a) {
				$a = pq($a);
				$name = $a->attr('name');

				// Make sure it's not in the keepers.
				if (!in_array($name, $keepers)) {
					// Remove the anchor when no descendants.
					$a_html = trim($a->html());
					if (empty($a_html)) {
						$a->remove();
						$removed ++;
					}
				}
			}
		}


		$this->remove_data['anchorNames'] = $removed;
	}

	protected function removeBackgroundColor($options = '') {
		$this->styles[] = 'background-color';
		$this->styles[] = 'background';
	}

	/**
	 * Removes any empty div elements.
	 */
	protected function removeEmptyDivs() {
		foreach ($this->content->find("div:empty") as $div) {
			$div = pq($div);
			$div->remove();
		}
		foreach ($this->content->find('div') as $div) {
			$div = pq($div);
			$div_html = $div->html();
			if (preg_match('/^\s+$/', $div_html)) {
				$div->remove();
			}
		}
	}

	/**
	 * Removes any empty paragraph elements.
	 */
	protected function removeEmptyParagraphs() {
		foreach ($this->content->find("p:empty") as $p) {
			$p = pq($p);
			$p->remove();
		}
		foreach ($this->content->find('p') as $p) {
			$p = pq($p);
			$p_text = $p->html();
			if (preg_match('/^\s+$/', $p_text)) {
				$p->remove();
			}
			if ($p_text === "&nbsp;") {
				$p->remove();
			}
		}
	}

	/**
	 * Removes any empty span elements.
	 */
	protected function removeEmptySpans() {
		// foreach ($this->content->find("span:empty") as $span) {
		// 	$span = pq($span);
		// 	$span->remove();
		// }
		foreach ($this->content->find('span') as $span) {
			$span = pq($span);
			$span_text = $span->html();
			if (preg_match('/^\s+$/', $span_text)) {
				$span->remove();
			}
		}
	}

	/**
	 * Removes font declarations from the content.
	 */
	protected function removeFont() {
		$this->content = $this->stripHtmlTag($this->content, 'font');
		$this->styles[] = 'font';
		$this->styles[] = 'font-family';
	}

	/**
	 * Removes declared font families.
	 */
	protected function removeFontFaces() {
		foreach ($this->content->find("font[face]") as $font) {
			$font = pq($font);
			$font->removeAttr('face');
		}
		$this->styles[] = 'font';
		$this->styles[] = 'font-family';
	}

	/**
	 * Removes declared font sizes.
	 */
	protected function removeFontSizes() {
		foreach ($this->content->find("font[size]") as $font) {
			$font = pq($font);
			$font->removeAttr('size');
		}
		$this->styles[] = 'font';
		$this->styles[] = 'font-size';
		$this->styles[] = 'line-height';
	}

	/**
	 * Removes heights from images.
	 */
	protected function removeImageHeights() {
		foreach ($this->content->find("img") as $image) {
			$image = pq($image);
			$image->removeAttr('height');
			$style_attr = $image->attr('style');
			$style_attr = explode(';', $style_attr);
			foreach ($style_attr as $key => $attr) {
				if (strpos(trim($attr), 'height:') === 0) {
					unset($style_attr[$key]);
				}
			}
			$style_attr = implode(';', $style_attr);
			$image->attr('style', $style_attr);
		}
	}

	/**
	 * Removes widths from images.
	 */
	protected function removeImageWidths() {
		foreach ($this->content->find("img") as $image) {
			$image = pq($image);
			$image->removeAttr('width');
			$style_attr = $image->attr('style');
			$style_attr = explode(';', $style_attr);
			foreach ($style_attr as $key => $attr) {
				if (strpos(trim($attr), 'width:') === 0) {
					unset($style_attr[$key]);
				}
			}
			$style_attr = implode(';', $style_attr);
			$image->attr('style', $style_attr);
		}
	}

	/**
	 * Removes the language attribute.
	 */
	protected function removeLang() {
		foreach ($this->content->find("[lang]") as $lang) {
			$lang = pq($lang);
			$lang->removeAttr('lang');
		}
	}

	/**
	 * Removes margin-left inline styles.
	 */
	protected function removeMarginLeft() {
		$this->styles[] = 'margin-left';
	}

	/**
	 * Removes the no wrap attribute.
	 */
	protected function removeNoWrap() {
		foreach ($this->content->find('[nowrap]') as $no_wrap) {
			$no_wrap = pq($no_wrap);
			$no_wrap->removeAttr('nowrap');
		}
	}

	protected function removePosition() {
		$this->styles[] = 'position';
	}

	/**
	 * Removes the matching inline style items
	 * @param array $styles Array of CSS inline styles to remove.
	 */
	protected function removeStyles($styles, $options = array()) {
		// Check options.
		if (!empty($options['removeStyles'])) {
			$styles = array_merge($styles, $options['removeStyles']);
		}

		// Remove any duplicate values.
		$styles = array_unique($styles);

		// Don't run when there are no styles to remove.
		if (count($styles) < 1) {
			return;
		}

		// Loop through each DOM element with a style attribute.
		ignore_user_abort(true);
		set_time_limit(0);
		foreach ($this->content->find("[style]") as $style) {
			$style = pq($style);

			// Make an array of the styles.
			$style_attr = $style->attr('style');
			$style_attr = explode(';', $style_attr);

			// Loop through the array of styles.
			foreach ($style_attr as $key => $attr) {
				// Loop through each of the styles to remove.
				foreach ($styles as $remove) {
					// If the style to remove is found, unset it.
					if (stripos(trim($attr), $remove . ':') === 0) {
						// Make sure the background color matches when the option is set.
						if (!empty($options['removeBackgroundColor'])) {
							if ($remove === 'background-color' || $remove === 'background') {
								$color_matches = false;
								foreach ($options['removeBackgroundColor'] as $color) {
									if (stripos($attr, $color) !== false) {
										$color_matches = true;
									}
								}
								if (!$color_matches) {
									continue;
								}
							}
						}

						// Don't remove courier.
						if ($remove === 'font-family' && stripos($attr, 'courier') !== false) {
							continue;
						}
						unset($style_attr[$key]);
					}
				}
			}

			// Put the styles that are left back together.
			$style_attr = implode(';', $style_attr);
			$style->attr('style', $style_attr);
		}
	}

	/**
	 * Removes text-align inline styles.
	 */
	protected function removeTextAlign() {
		$this->styles[] = 'text-align';
	}

	/**
	 * Removes text indenting inline styles.
	 */
	protected function removeTextIndent() {
		$this->styles[] = 'text-indent';
	}

	/**
	 * Removes table body element.
	 */
	protected function removeTableBodies() {
		$this->content = $this->stripHtmlTag($this->content, 'tbody');
	}

	/**
	 * Removes table head element.
	 */
	protected function removeTableHeads() {
		$this->content = $this->stripHtmlTag($this->content, 'thead');
	}

	/**
	 * Removes heights from table and table cells.
	 */
	protected function removeTableHeights() {
		foreach ($this->content->find("table[height]") as $table) {
			$table = pq($table);
			$table->removeAttr('height');
		}
		foreach ($this->content->find("tr") as $tr) {
			$tr = pq($tr);
			$tr->removeAttr('height');
			$style_attr = $tr->attr('style');
			$style_attr = explode(';', $style_attr);
			foreach ($style_attr as $key => $attr) {
				if (strpos(trim($attr), 'height:') === 0) {
					unset($style_attr[$key]);
				}
			}
			$style_attr = implode(';', $style_attr);
			$tr->attr('style', $style_attr);
		}
		foreach ($this->content->find("td") as $td) {
			$td = pq($td);
			$td->removeAttr('height');
			$style_attr = $td->attr('style');
			$style_attr = explode(';', $style_attr);
			foreach ($style_attr as $key => $attr) {
				if (strpos(trim($attr), 'height:') === 0) {
					unset($style_attr[$key]);
				}
			}
			$style_attr = implode(';', $style_attr);
			$td->attr('style', $style_attr);
		}
	}

	protected function removeTablePadding() {
		foreach ($this->content->find("table[cellpadding]") as $table) {
			$table = pq($table);
			$table->removeAttr('cellpadding');
		}
		foreach ($this->content->find("td") as $td) {
			$td = pq($td);
			$style_attr = $td->attr('style');
			$style_attr = explode(';', $style_attr);
			foreach ($style_attr as $key => $attr) {
				if (strpos(trim($attr), 'padding') === 0) {
					unset($style_attr[$key]);
				}
			}
			$style_attr = implode(';', $style_attr);
			$td->attr('style', $style_attr);
		}
	}

	/**
	 * Removes widths from table and table cells.
	 */
	protected function removeTableWidths() {
		foreach ($this->content->find("table") as $table) {
			$table = pq($table);
			$table->removeAttr('width');
			$style_attr = $table->attr('style');
			$style_attr = explode(';', $style_attr);
			foreach ($style_attr as $key => $attr) {
				if (strpos(trim($attr), 'width:') === 0) {
					unset($style_attr[$key]);
				}
			}
			$style_attr = implode(';', $style_attr);
			$table->attr('style', $style_attr);
		}
		unset($table);
		foreach ($this->content->find("td") as $td) {
			$td = pq($td);
			$td->removeAttr('width');
			$style_attr = $td->attr('style');
			$style_attr = explode(';', $style_attr);
			foreach ($style_attr as $key => $attr) {
				if (strpos(trim($attr), 'width:') === 0) {
					unset($style_attr[$key]);
				}
			}
			$style_attr = implode(';', $style_attr);
			$td->attr('style', $style_attr);
		}
		unset($td);
	}

	protected function removeZindex() {
		$this->styles[] = 'z-index';
	}

	/**
	 * Strips the provided HTML tag from the string.
	 * @param {String} $str String to edit.
	 * @param {String} $tags Tag to remove from the string.
	 * @param {Boolean}  $stripContent Whether to remove the content inside the tag.
	 * @return {String} $str The string with the tag removed.
	 */
	public function stripHtmlTag($str, $tags, $stripContent = false) {
		$content = '';
		if(!is_array($tags)) {
			$tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
			if(end($tags) == '') array_pop($tags);
		}
		foreach($tags as $tag) {
			if ($stripContent)
				 $content = '(.+</'.$tag.'[^>]*>|)';
			 $str = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $str);
		}
		return $str;
	}
}
