# Frontend Rules (Bootstrap + Vanilla JS)

## HTML Structure
- Semantic HTML always — nav, main, section, article, header, footer
- One responsibility per HTML file — don't mix unrelated UI in same file
- IDs for unique elements only — classes for reusable elements
- Keep HTML clean — no inline styles, no inline event handlers (onclick="")
- All IDs and class names in kebab-case: `user-card`, `submit-btn`

## Bootstrap Usage
- Use Bootstrap utility classes first — don't write custom CSS if Bootstrap already has it
- Never override Bootstrap with `!important` — extend properly via custom CSS variables
- Use Bootstrap's spacing scale: m-1, p-3, mb-4 etc. — no magic pixel values
- Use Bootstrap grid system (container, row, col) for all layouts — no custom grid
- Use Bootstrap components (modal, dropdown, toast) — don't build from scratch
- Responsive breakpoints order: xs → sm → md → lg → xl — mobile first always
- Use Bootstrap color system: `text-primary`, `bg-danger` etc. — no hardcoded hex colors

## Custom CSS Rules
- Custom CSS goes in one file only — never scattered across multiple files
- Use CSS custom properties (variables) for repeated values:
  ```css
  :root { --brand-color: #3490dc; }
  ```
- Class naming: BEM style — `.card__title`, `.card--active`
- Never use inline styles in HTML — always use classes
- Keep specificity low — avoid deep nesting like `.parent .child .grandchild`

## JavaScript Structure
- One JS file per page/feature — `dashboard.js`, `login.js`
- All JS files go in `/js/` or `/assets/js/` folder
- Use `const` and `let` only — never `var`
- Functions must be small and do one thing only
- Use descriptive names: `fetchUserById()` not `getData()`
- Group related functions together with a comment header

## DOM Manipulation
- Cache DOM elements at top of file — never query same element twice:
  ```js
  // ✅ correct — cache at top
  const submitBtn = document.getElementById('submit-btn');
  ```
- Use `addEventListener` always — never inline `onclick` in HTML
- Remove event listeners when element is destroyed to avoid memory leaks
- Use `data-*` attributes to store metadata on HTML elements

## AJAX / Fetch
- Every fetch must handle three states: loading, success, error
- Show Bootstrap spinner during every API call
- Disable submit buttons during API call — re-enable after response
- Always show user-friendly error messages — never show raw error to user
- Use async/await — not `.then().catch()` chains
- Standard fetch pattern:
  ```js
  async function submitForm() {
    toggleLoading(true);
    try {
      const res = await fetch('/api/endpoint', { method: 'POST', body: ... });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message);
      showSuccess('Saved successfully');
    } catch (err) {
      showError(err.message);
    } finally {
      toggleLoading(false);
    }
  }
  ```

## Forms
- Validate on frontend AND confirm on backend — never trust frontend-only validation
- Show inline validation errors using Bootstrap's `is-invalid` + `invalid-feedback`
- Disable submit button after click to prevent double submission
- Clear form after successful submission
- Use `FormData` for file uploads

## Alerts & Feedback
- Use Bootstrap Toast for non-blocking notifications
- Use Bootstrap Modal for confirmations (delete, important actions)
- Use Bootstrap Alert for inline page-level messages
- Never use `alert()` or `confirm()` — use Bootstrap components instead
- Auto-dismiss success toasts after 3 seconds

## Performance
- Load Bootstrap CSS in `<head>`, all JS before `</body>`
- Minify custom CSS and JS for production
- Images must have `width`, `height`, and `alt` attributes always
- Lazy load images below the fold: `loading="lazy"`

## Accessibility
- Every button must have visible text or `aria-label`
- Form inputs must have `<label>` with matching `for` attribute
- Bootstrap modals must have proper `aria-labelledby`
- Never remove focus outline — Bootstrap handles it, don't override
- Use Bootstrap's `.visually-hidden` for screen-reader-only text

## Strict No Inline Rules (NON-NEGOTIABLE)
- NEVER write inline CSS — `style="..."` is strictly forbidden on any HTML element
- NEVER write inline JavaScript — `onclick="..."`, `onchange="..."`, `onsubmit="..."` strictly forbidden
- ALL styles must be in external `.css` files only
- ALL JavaScript must be in external `.js` files only
- No `<style>` tags inside HTML files — not even for "quick fixes"
- No `<script>` tags with JS code inside HTML files — only `<script src="...">` allowed

## What NOT to Do
- Do not use jQuery unless project already uses it
- Do not write custom modals/dropdowns — use Bootstrap's built-in
- Do not use `document.write()`
- Do not use `var` — always `const` or `let`
- Do not leave `console.log` in production code
- Do not manipulate DOM inside loops — batch updates
- Do not load entire icon libraries for 2-3 icons — use Bootstrap Icons SVG