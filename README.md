# KINZ Jewellery — HTML/CSS-only frontend

This version removes the separate JavaScript files and inline JavaScript blocks. Existing HTML and CSS styling are retained as far as possible.

## Important limitations
HTML and CSS cannot replace JavaScript for dynamic cart updates, search autocomplete, wishlist state, checkout calculations, or success pop-up alerts. Those interactions will be inactive in this static version. Product areas previously generated at runtime may appear empty. To preserve those features without DOM manipulation, their content must be written directly into each HTML page or rendered by the PHP backend.

Open `index.html` to preview the static frontend.
