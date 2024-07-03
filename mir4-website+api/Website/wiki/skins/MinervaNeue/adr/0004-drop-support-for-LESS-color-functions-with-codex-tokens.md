# 1. Drop support for Less color functions with Codex design tokens

Date: 2024-02-16
Last Edited: 2024-03-14

## Status

Accepted.

## Context

In order to reduce the strain on other teams/developers to update their extensions to use
CSS variables rather than LESS, we decided to use [Wikimedia skin variables](https://www.mediawiki.org/wiki/Codex#Using_Codex_design_tokens_in_MediaWiki_and_extensions) to re-map LESS variables to their
CSS variable equivalent.

e.i. `@color-progressive: var( --color-progressive )`;

This is consistent with the Codex design token experimental build which we will
make use of in [T358059](https://phabricator.wikimedia.org/T358059).

This approach has implications on LESS function that manipulate colors, such as
`average`, `fade` and `tint`. These functions expect a parameter of type color.
This approach changes the variable type from color to string, causing these
functions to fail.

## Decision

Given there are few consumers of these LESS functions, we've decided to remove support
for LESS functions that operate on colors. The benefit of this decision is that
it will reduce non-standard colors across the UI.

## Consequences

Extensions that need to work with MinervaNeue for the time being can no longer
use Less color functions with Codex design tokens. They can continue to use
these function if they instead make use of hardcoded hex values (with an
accompanying comment explaining this reason).

We filed [a ticket](https://phabricator.wikimedia.org/T357740) for addressing this on the
long term. This may require upgrading our version of LESS to a more modern
version or a decision to drop support altogether.
