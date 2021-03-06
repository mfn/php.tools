<?php
final class ReindentEqual extends FormatterPass {
	public function candidate($source, $foundTokens) {
		return true;
	}

	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';

		for ($index = sizeof($this->tkns) - 1; 0 <= $index; --$index) {
			$token = $this->tkns[$index];
			list($id) = $this->getToken($token);
			$this->ptr = $index;

			if (ST_SEMI_COLON == $id) {
				--$index;
				$this->scanUntilEqual($index);
			}
		}

		return $this->render($this->tkns);
	}

	private function scanUntilEqual($index) {
		for ($index; 0 <= $index; --$index) {
			$token = $this->tkns[$index];
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;

			switch ($id) {
			case ST_CURLY_CLOSE:
				$this->refWalkCurlyBlockReverse($this->tkns, $index);
				break;

			case ST_PARENTHESES_CLOSE:
				$this->refWalkBlockReverse($this->tkns, $index, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
				break;

			case ST_BRACKET_CLOSE:
				$this->refWalkBlockReverse($this->tkns, $index, ST_BRACKET_OPEN, ST_BRACKET_CLOSE);
				break;

			case ST_CONCAT:
			case T_STRING:
			case T_VARIABLE:
			case ST_TIMES:
			case ST_DIVIDE:
			case ST_PLUS:
			case ST_MINUS:
			case T_POW:
				break;

			case T_WHITESPACE:
				if (
					$this->hasLn($text)
					&&
					!
					(
						$this->rightUsefulTokenIs([ST_SEMI_COLON])
						||
						$this->leftUsefulTokenIs([
							ST_BRACKET_OPEN,
							ST_COLON,
							ST_CURLY_CLOSE,
							ST_CURLY_OPEN,
							ST_PARENTHESES_OPEN,
							ST_SEMI_COLON,
							T_END_HEREDOC,
							T_OBJECT_OPERATOR,
							T_OPEN_TAG,
						])
						||
						$this->leftTokenIs([
							T_COMMENT,
							T_DOC_COMMENT,
						])
					)
				) {
					$text .= $this->indentChar;
					$this->tkns[$index] = [$id, $text];
				}
				break;

			default:
				return;
			}
		}
	}
}
