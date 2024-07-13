Chunk-based serialization support.

Keeping wikitext output in `ConstrainedText` chunks allows us to
preserve meta-information about boundary conditions at the edges
of chunks.  This allows us to more easily add `<nowiki>` and other
fixups where needed to prevent misparsing caused by juxtaposition.

For example, the chunk corresponding to a magic link can "remember"
that it needs to have word boundaries on either side.  If these aren't
present (after the chunks on either side have been serialized) then
we can add <nowiki> escapes at the proper places.
