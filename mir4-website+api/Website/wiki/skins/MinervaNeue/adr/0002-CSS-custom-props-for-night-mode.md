# 1. Use CSS custom properties for night mode

Date: 2024-01-29

## Status

Accepted.

## Context

During the Night Mode Project, there was considerable debate as to how to
technically achieve night mode given the ecosystem and infrastructure
complexities of the Wikimedia ecosystem.

### Considered approaches

**Use the existing DarkMode gadget** - This approach relies on using a CSS
invert "hack" to invert all the colors on the rendered page and change them from
light to dark. This was the initially proposed strategy, but it was
later dropped for the following reasons:

- **Semantic color distortion**: Inverting all colors on the page would make certain
  colors incorrect. This would negatively impact Wikipedia content in a way that
  may make it wrong or confusing (e.g. inverting [political party](https://en.wikipedia.org/wiki/Congress_of_the_Philippines)/sports team
  colors or [articles about colors](https://en.wikipedia.org/wiki/Pantone#Color_of_the_Year) ). It was deemed that in principle, the
  Wikimedia foundation should not make these kinds of potentially negative
  changes to Wikimedia project content.
- **Inability to measure impact**: When inverting colors, we would have no way of
  measuring the amount of content that has been negatively impacted, and
  therefor would not be able to identify which content needs fixing.
- **Lack of color palette customization**: Inverting colors would not provide
  any flexibility for tweaking or modifying the night mode design. The resulting
  night mode would be an inverse of the light-mode, instead of a more nuanced
  design. Visual elements such as colors or white drop-shadows would be technically
  difficult to remove or modify.

**Modify the existing Less variables** - This approach would involve changing the
values of the existing color variables in the Less CSS preprocessor based on
night mode being enabled or disabled. Since Codex variables are widely used and
overrideable by the skin, it would have broad ecosystem support. It was
not pursued for the following reasons:

- **Support for anonymous users**: In order to serve night mode to anonymous
  users, we would have to generate a new night mode stylesheet, then serve that
  stylesheet conditionally for anonymous users based on whether they or not
  have night mode enabled. Due to our aggressive caching strategy, we are
  unable to vary which stylesheets we serve to anonymous users.

**Use CSS custom properties for colors** - This approach involves replacing the
Less color variables required for night mode with CSS custom properties.
Conditional code is then added to the stylesheet (either by media-query or
class) that changes the value of these custom properties for night mode. Due to
the constrains mentioned above, this is the preferred approach.

## Decision

In order to create a night mode for the Minerva skin, the Web Team has decided to
use CSS custom properties to achieve a customizable color palette.

We will replace the Less variables required for night mode with CSS custom
properties. The values of these custom properties will remain unchanged for
light-mode, but will be customized for night mode.

We agree that maintaining a single source of truth for Codex token values is
important, so we will maintain the Codex naming convention for CSS custom
properties and use values derived from Codex as much as possible.

## Consequences

### Benefits

- **Performance** - Night mode can be implemented by changing a
  relatively small set of CSS values.
- **Better alignment with web standards** - CSS custom properties are a
  standards-based solution with broad browser support.
- **Better traceability & tooling**. Unlike Less variables, changes to CSS custom
  properties are easily traceable via the browser console.
- **Better support for system-level settings** - CSS custom properties can
  respond to the `prefers-color-scheme` media-query, which will allow us to
  enable/disable night mode based on system-level settings. *In future, we can
  also respond to other accessibility media-queries such `prefers-contrast`*.

### Drawbacks

Although the Minerva skin can implement CSS custom properties with relative ease,
we acknowledge that the Mediawiki ecosystem depends on on a broad range of
extensions, and for these to function well in night mode, they too will have to
adopt the same CSS custom properties as the Minerva skin. We commit to helping
these migration by providing strategies, documentation and tooling around the
expect use of CSS custom properties for night mode.

Similarly, we acknowledge that templates will also have to be modified to work
with this night mode strategy. We commit to creating plans or strategies to
help template authors with these modifications.