# 1. Reduce support for browsers that do not support CSS Custom Properties

Date: 2024-02-01

## Status

Accepted.

## Context

In order to develop night mode in a sustainable and performant way, the Web team
decided to use CSS custom properties for all colors in the Minerva skin. This
allows us to develop night mode by simply changing CSS custom property values.

## Decision

we will reduce support for browsers that do not support CSS custom properties,
most notably Opera Mini.

According to caniuse the main browsers this impacts are IE11 and Opera Mini:
https://caniuse.com/css-variables

According to https://github.com/Fyrd/caniuse/issues/3883 CSS variables are not
available only in its non-default "extreme mode".

According to the following stats:
https://analytics.wikimedia.org/dashboards/browsers/#mobile-site-by-browser
Opera Mini represents **0.1%** of traffic to the mobile site. Given this tiny
percentage of traffic, the maintenance and performance cost of providing a
fallback for older browsers is deemed too high (since it would involve
increasing the CSS payload to the remaining 99.9% of users).

## Consequences

Browsers like IE11 and Opera Mini will degrade gracefully into a reduced color
experience. Colors will fallback to the default user-agent stylesheet so links
will be blue, text will be black and background colors will be white. Although this
will be a grade C experience, it will still provide adequate legibility and
accessibility for basic functionality (i.e. reading).
