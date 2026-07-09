# Changelog


## [Released] v1.0 - First Tagged release - HTTP Header Underscore Support & Input Normalization

### Added
- **Added CHANGELOG.md to start tracking changes:**. This will allow us to start tagging releases. 
- **Support for `FIX_` Mapped Headers:** Enhanced `get_normalized_headers()` to scan for variables starting with a `FIX_` prefix in the `$_SERVER` superglobal. This maps and normalizes variables like `FIX_CHAR_ID` to standardized lowercase underscored (`char_id`) and hyphenated (`char-id`) formats so they merge seamlessly into the final `$input` array.

### Changed
- **Fixed Header Stripping in PHP-FPM/Apache:** Removed the dependency on `getallheaders()` inside `get_normalized_headers()`. This ensures that even when Apache filters out custom headers containing underscores from its native request header array, the PHP fallback block parsing the `$_SERVER` superglobal is always executed.
- **Optimized Input Array Fallback:** Refactored payload initialization so that if `json_decode(file_get_contents('php://input'))` returns `null` (due to an empty or non-JSON body), `$input` is cleanly initialized to an empty array `[]` instead of `null`. This prevents PHP type errors when merging headers.

### Removed
- **Cleaned Up API Outputs:** Removed and commented out raw `echo` and `echo json_encode()` diagnostic statements (such as echoing raw `$_SERVER` or `$input` arrays). This prevents malformed plain-text prefixes from corrupting clean JSON responses expected by Swagger UI and other API clients.

---

## PR Description Summary

This change resolves a critical issue where Custom HTTP Headers containing underscores (such as `char_id` or `token`) were stripped or ignored when running PHP-FPM behind an Apache web server. To safely bypass this limitation, we introduced support for mapping and normalizing headers prefixed with `FIX_` (e.g., `FIX_CHAR_ID`) as drop-in replacements for standard `HTTP_` headers.

Additionally, this branch cleans up core request handling by removing fragile dependencies on Apache-specific PHP functions, optimizing payload parsing, and removing raw diagnostic output statements that were corrupting JSON API payloads.

### Verification Checklist

- [x] Standard `HTTP_` prefixed headers are successfully parsed and normalized.
- [x] `FIX_` prefixed headers are identified, stripped of their prefix, normalized, and merged into the final `$input` payload.
- [x] API endpoints return clean JSON payloads without raw text debugging leaks.
- [x] `OPTIONS` preflight requests continue to exit cleanly with a `24 No Content` status.

## Previous development / changes not tracked in this changelog.