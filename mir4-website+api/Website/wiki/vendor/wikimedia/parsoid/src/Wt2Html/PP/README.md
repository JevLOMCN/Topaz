DOM Post-Processing
===================

The distinction here is that `/handlers` are used with the DOMTraverser,
whereas `/processors` have their own DOM traversal code.  Arguably, the
processors should be ported to the unified interface.
