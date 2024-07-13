# 1. Adopt ADRs (Architectural decision records)

Date: 2024-01-29

## Status

Accepted.

Options:
- Proposed - If a decision is still pending acceptance.
- Accepted - If a decision has been accepted by the maintaining team.
- Deprecated - If a decision is no longer relevant.
- Superseded - If a decision has been replaced with a newer one. Should include
  link to the newer ADR.

## Context

As the code and teams maintaining this repository change over time, the
knowledge of why certain decisions were made in the past may be lost or poorly
understood. In order to help future maintainers of this repository (as well as
our future selves) make sense of this code, we need to keep records of any
significant, atypical or otherwise notable decisions.

Since architectural decisions are made gradually, these records should be easily
editable and added incrementally as changes arise.

## Decision

We will adopt architecture decision records. This file serves as a template for
ADRs. ADRs will be numbered markdown files placed in the /adr directory.
The format will follow the Michael Nygard ARD style as described on the
following web page:

https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions.html

More guidance on how to write ADRS:
https://adr.github.io/

## Consequences

When any important, atypical or otherwise notable decision is made within this
codebase, an ADR should be created and placed inside this directory. It is up to
the person with the most context to create the ADR with regards to that decision.
The ADR should be brief and may link to Phabricator or other places that hold
more context around the decision.

ADRs may also be used to propose changes by framing the ADR as a proposal and
marking the status as "proposed". If there is agreement to adopt the ADR, it's
status should be change to "accepted".

ADRs can also be marked "deprecated" or "superseded".


