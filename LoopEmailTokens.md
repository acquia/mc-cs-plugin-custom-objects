## Custom object loop tokens

Custom items can be rendered in emails (or other content) using loop tokens.

### Basic syntax

```text
{custom-object-loop object=product-views | where=segment-filter | order=latest | limit=10}
  {custom-object-loop-value object=product-views | field=name | default=jhon}
{/custom-object-loop}
```

- **`{custom-object-loop ...}`**: Opens the loop and defines which custom items to load.
- **`{/custom-object-loop}`**: Closes the loop. Everything between the opening and closing tags is repeated for each matching custom item.
- **HTML inside the loop**: You can place any HTML (or other tokens) between the loop tags; that block will be repeated.

### Loop parameters (`custom-object-loop`)

- **object**: Custom object name (e.g. `product-views`). Required.
- **where**:
  - Only `segment-filter` is implemented so far (and it is the default if omitted).
  - It takes the first segment of a segment email, or the first campaign source segment of a campaign email.
  - All custom-object-related filters from that segment are converted into `WHERE` conditions.
- **order**:
  - Only `latest` is supported (and is the default if omitted).
  - Items are ordered from newest to oldest by their creation date.
- **limit**:
  - Default is `1` if not provided.
  - If greater than `1`, multiple items are rendered.

### Loop-value parameters (`custom-object-loop-value`)

- **object**: Must match the `object` used in the surrounding `custom-object-loop`. If it does not match, it is ignored.
- **field**: Field alias of the custom object whose value should be rendered (for example `name`, `price`, `color`).
- **default**: Fallback value if the field is empty (for example `default=Unknown`).

### Examples

**List product names bought by the contact:**

```text
{custom-object-loop object=product-views | where=segment-filter | order=latest | limit=3}
  {custom-object-loop-value object=product-views | field=name | default=Unknown product}<br>
{/custom-object-loop}
```

Rendered example:

```html
Mautic T-shirt<br>
Mautic Hoodie<br>
Mautic Cap<br>
```

**Render a small HTML block per product:**

```html
{custom-object-loop object=product-views | where=segment-filter | order=latest | limit=2}
  <div class="product">
    <strong>{custom-object-loop-value object=product-views | field=name | default=Unknown}</strong><br>
    Price: {custom-object-loop-value object=product-views | field=price | default=0}<br>
    Color: {custom-object-loop-value object=product-views | field=color | default=Unknown}
  </div>
{/custom-object-loop}
```

Rendered HTML (for 2 products):

```html
<div class="product">
  <strong>Mautic T-shirt</strong><br>
  Price: 123<br>
  Color: Blue
</div>
<div class="product">
  <strong>Mautic Hoodie</strong><br>
  Price: 153<br>
  Color: Red
</div>
```

### Warnings and limitations

- **Multiple segments**: If multiple segments are used for segment emails or campaign sources, each segment must have identical custom object filters. Only the filters of the **first** segment are used during token replacement when sending emails.
- **No OR conditions**: Do not use `OR` conditions in the source segments for custom object filters.
- **No include/exclude membership filters**: Do not use include/exclude segment membership filters when relying on these tokens. Token replacement only considers filters on the root segment, not on all included/excluded segments.
