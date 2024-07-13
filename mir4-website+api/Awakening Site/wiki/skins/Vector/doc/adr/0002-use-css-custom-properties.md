# 2. Use CSS custom properties in Vector

Date: 2023-10-23

## Status

Accepted.

## Context

In the context of the [Accessibility for Reading project](https://www.mediawiki.org/wiki/Reading/Web/Accessibility_for_reading) we needed a way to enable font-size customization in Vector in a scalable way. CSS custom properties were the mechanism chosen to enable this functionality. Full discussion available on [T346072](https://phabricator.wikimedia.org/T346072).

## Decision
CSS Custom properties will be implemented in Vector on a very tightly  scoped basis. The implementation assumes that we will eventually want to up-stream CSS custom properties to MediaWiki core/Codex.

In order to facility an eventual migration, the following implementation measures will be taken:

- CSS custom properties will be used *only* when needed for client-size customization.
- CSS custom properties will be grouped in the following file
 `resources/skins.vector.styles/CSSCustomProperties.less`.
- CSS custom properties will maintain consistency with the naming convention of Codex design tokens.
- When possible, we'll assign CSS custom properties to existing Codex token values.

## Consequences

CSS Custom properties will be marked as `@private` and `@experimental` in the spirit of the [Stable interface policy/Frontend](https://www.mediawiki.org/wiki/Stable_interface_policy/Frontend) in order to discourage usage outside of Vector. This could be changes to `@internal` if usage outside of Vector is deemed necessary.