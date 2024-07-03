# 5. Adopt ADRs (Architectural decision records)

Date: 2024-03-11

## Status

Accepted

## Context

As the team works on developing night mode, the largest hurdle remains user-
generated content.  One particular class of user-generated content that we've
run up against is the use of inline styles to define a background color, with
the assumption the text color would remain constant.  With the introduction of
night mode, this assumption is broken, and we are running into color contrast
issues for light-colored text on a background that was presumed to be
contrasting with a darker text color

## Decision

After evaluating the expected performance impacts, we have decided that a CSS
approach targeting `[style*=background]` that sets the color explicitly to
`#333` would be sufficient to address this concern.  While we considered a php-
based parser approach, it was found that the CSS approach was performant enough
to meet our needs and considerably more straightforward to implement

See https://phabricator.wikimedia.org/T358240#9591458 for further details

## Consequences

The most direct consequence of this change is that we will be modifying the
display of the page from what the authors explicitly intended for it to be.
We're quite confident that in the vast majority of cases this will not be an
issue, as the omission of font color was most likely predicated upon the
assumption it would remain the same in all cases, but we understand and
acknowledge that this could lead to issues where a user is unhappy with the
color being modified.  To mitigate this issue, we have deliberately written the
style such that a defined color will take precendence over this, so ideally the
risk of this is low
