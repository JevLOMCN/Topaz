# 3. Publish client preferences

Date: 2024-01-11

## Status

In discussion.

## Context

As we build out dark mode, font size and other client preference controls, we want
to avoid having to build things twice for two different skins. The existing code
for client preferences lives in Vector. We expect the code to evolve as different
use cases emerge and when the code/UI evolves we want those changes to be seen
on both skins without having to maintain two separate implementations.

We expect as features like dark mode and font size roll out so that other skins
will want to make use of this code.

## Decision

For now, we will publish a library @wikimedia/mediawiki.skins.clientpreferences that
will be used inside MobileFrontend.

When we make significant changes to the the code in Vector, we will update the
version in the mobile site.

See https://wikitech.wikimedia.org/wiki/Npm_registry for details on the @wikimedia
organization on NPM.

See https://docs.npmjs.com/creating-and-publishing-scoped-public-packages
When making modifications to the code inside the Vector skin, to use these in the mobile site
we would create a new release of the module by cd-ing into the directory, bumping the version
in the package.json file and using `npm publish`. Then inside MobileFrontend we would pull
down the latest code from npm and run the build step.

## Consequences

On the long term we expect the library @wikimedia/mediawiki.skins.clientpreferences
to become obsolete. This may involve upstreaming the stable library to MediaWiki
core or creating a new extension to house the code.
