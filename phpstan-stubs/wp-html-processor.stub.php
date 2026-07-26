<?php
/**
 * PHPStan stub for the WP core HTML Tag Processor API (WP 6.4+,
 * wp-includes/html-api/class-wp-html-processor.php). phpstan-wordpress
 * doesn't ship signatures for this class, so PHPStan treats its methods
 * as pure and reasons (incorrectly) that repeated calls in a loop always
 * return the same value. These are stateful iterator methods — each call
 * advances/reads the processor's internal cursor — so they're annotated
 * @phpstan-impure here rather than suppressing the resulting findings.
 */
class WP_HTML_Processor {
	public static function create_fragment( $html, $context = '<body>', $encoding = 'UTF-8' ): ?self {}

	/** @phpstan-impure */
	public function next_token(): bool {}

	/** @phpstan-impure */
	public function get_token_type(): ?string {}

	/** @phpstan-impure */
	public function get_tag(): ?string {}

	/** @phpstan-impure */
	public function is_tag_closer(): bool {}

	/** @phpstan-impure */
	public function get_attribute( $name ) {}

	/** @phpstan-impure */
	public function serialize_token(): string {}
}
