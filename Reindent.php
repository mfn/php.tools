<?php
final class Reindent extends FormatterPass {
	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';
		$found_stack = [];
		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->get_token($token);
			$this->ptr = $index;

			if (
				(
					T_WHITESPACE === $id ||
					(T_COMMENT === $id && '//' == substr($text, 0, 2))
				) && substr_count($text, $this->new_line) > 0
			) {
				$bottom_found_stack = end($found_stack);
				if (isset($bottom_found_stack['implicit']) && $bottom_found_stack['implicit']) {
					$idx = sizeof($found_stack) - 1;
					$found_stack[$idx]['implicit'] = false;
					$this->set_indent(+1);
				}
			}
			switch ($id) {
				case ST_QUOTE:
					$this->append_code($text, false);
					$this->printUntilTheEndOfString();
					break;
				case T_CLOSE_TAG:
					$this->append_code($text, false);
					while (list($index, $token) = each($this->tkns)) {
						list($id, $text) = $this->get_token($token);
						$this->ptr = $index;
						$this->append_code($text, false);
						if ($id == T_OPEN_TAG) {
							break;
						}
					}
					break;
				case T_START_HEREDOC:
					$this->append_code(rtrim($text) . $this->get_crlf(), false);
					break;
				case T_CONSTANT_ENCAPSED_STRING:
				case T_ENCAPSED_AND_WHITESPACE:
				case T_STRING_VARNAME:
				case T_NUM_STRING:
					$this->append_code($text, false);
					break;
				case T_CURLY_OPEN:
				case ST_CURLY_OPEN:
				case ST_PARENTHESES_OPEN:
				case ST_BRACKET_OPEN:
					$indent_token = [
						'id' => $id,
						'implicit' => true
					];
					$this->append_code($text, false);
					if ($this->has_ln_after()) {
						$indent_token['implicit'] = false;
						$this->set_indent(+1);
					}
					$found_stack[] = $indent_token;
					break;
				case ST_CURLY_CLOSE:
				case ST_PARENTHESES_CLOSE:
				case ST_BRACKET_CLOSE:
					$popped_id = array_pop($found_stack);
					if (false === $popped_id['implicit']) {
						$this->set_indent(-1);
					}
					$this->append_code($text, false);
					break;

				default:
					if (substr_count($text, $this->new_line) > 0 && !$this->is_token(ST_CURLY_CLOSE) && !$this->is_token(ST_PARENTHESES_CLOSE) && !$this->is_token(ST_BRACKET_CLOSE)) {
						$text = str_replace($this->new_line, $this->new_line . $this->get_indent(), $text);
					} elseif (substr_count($text, $this->new_line) > 0 && ($this->is_token(ST_CURLY_CLOSE) || $this->is_token(ST_PARENTHESES_CLOSE) || $this->is_token(ST_BRACKET_CLOSE))) {
						$this->set_indent(-1);
						$text = str_replace($this->new_line, $this->new_line . $this->get_indent(), $text);
						$this->set_indent(+1);
					}
					$this->append_code($text, false);
					break;
			}
		}
		return $this->code;
	}

}
