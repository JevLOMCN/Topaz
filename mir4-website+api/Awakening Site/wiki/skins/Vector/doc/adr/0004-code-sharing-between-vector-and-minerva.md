# 4. Code sharing between Vector and Minerva Skins

Date: 2024-03-07
Updated: 2024-03-19

## Status

Accepted.

## Context

Exploring effective strategies for code sharing between Vector and Minerva Skins to reduce duplication and improve maintenance. Key options considered include creating a Composer library for shared functionalities, duplicating code in both skins, and moving shared code to the MediaWiki core.

On the short term this relates to solving the problem at T359606 and on the long term this might apply to things like the feature management system.

## Decision

After thorough consideration, the following options are under evaluation for code sharing between Vector and Minerva Skins:
- **Creating a Composer Library**: Centralizing shared functionalities to reduce duplication and improve update management.
- **Duplicating Code**: Implementing similar functionalities independently in both skins, prioritizing flexibility and skin-specific optimizations.
- **Moving Shared Code to MediaWiki Core**: Leveraging the core platform to provide shared functionalities, enhancing accessibility and consistency across skins.

After some more discussion some other options emerged:
- **A git submodule** that is initialized inside Minerva and Vector.
- **A shell script** that copies the files from Vector to Minerva.
- **Making Minerva depend on Vector** add a soft dependency on Vector from Minerva.

We assessed each option on development efficiency, maintenance, and the ability to support future enhancements. After discussion there was agreement among the team about the following:
- Composer libraries should not depend on MediaWiki in any way.
- Ideally shared skin code should live in MediaWiki core
- Code in core should not be specific to Minerva and Vector and should be generally useful and should not be rushed.

We recognized the fact that often the web team needs to balance shipping products and agreed on the following:
**On the short term we would make Minerva depend on Vector**

Reasoning:
- This minimizes disruption to third parties. If we were to use a submodule, this would alter how people upgrade/install Vector.
- We think that there are few installs where Minerva is installed but Vector isn't. In Minerva we can use a soft dependency to avoid breaking changes.
- It would avoid issues relating to ensure Minerva and Vector's submodules are in sync and using the same version. For example, it would be possible
with the submodule approach that Vector and Minerva use different versions of the shared skin.
- Minerva would automatically benefit from updates to Vevtor.
- Since Minerva and Vector are in the CI pipeline, our integration tests should protect against making breaking changes.

## Consequences

The shared repository is intended as a way to manage code shared between Vector and Minerva more efficiently. However the expectation is that when
code is shared it should eventually be upstreamed to core and a Phabricator ticket should document that.
For example, T360452 has been created for the long term solution for our immediate need.

The sharing of code, means we must consider the Vector code a stable interface for Minerva to use.

