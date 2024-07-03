# 1. Use ADRs in Vector

Date: 2023-10-18

## Status

Accepted.

## Context

Vector has undergone many changes since the development of Vector 2022
began in 2019. Since that time there have been many changes to the skin that
have shaped its architecture and had implications on the MediaWiki
platform and broader ecosystem.
In order to better understand, and keep a record of, these and future
changes, we are implementing architectural decision records (ADRs).

## Decision

We are adopting architecture decision records. The format of these
records are numbered markdown files with the /doc/adr directory.
The format will follow the Michael Nygard ARD style as described
on the following web page:

https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions.html

## Consequences

When an decision that is deemed important to the codebase or the ecosystem
is made inside this repository, it is up to the deciding individuals
to create a new markdown file in this directory and add an ADR with regards
to that decision.The ADR should be brief and may link to Phabricator or
other places that hold more context around the decision.

ADRs may also be used to propose changes by framing the ADR as a proposal
and marking the status as "proposed". If there is agreement to adopt the
ADR, it's status should be change to "accepted".

ADRs can also be marked "deprecated" or "superseded".


