" Vim syntax file
" Language:	Wikitext parser tests
"
" README:
" 1. Copy this to ~/.vim/syntax/ptest.vim
" 2. Add something equivalent to the next line to your ~/.vimrc
"    autocmd BufNewFile,BufRead */subbu/work/wmf/**/tests/**txt set syntax=ptest

" quit when a syntax file was already loaded
if exists("b:current_syntax")
  finish
endif

syn case ignore

syn match comment /^#.*$/
syn match test /!!\s*\(test\|end\)/
syn match article /!!\s*\(article\|text\|endarticle\)/
syn match sections /!!\s*\(html\|wikitext\)/
syn match config /!!\s*\(options\|config\)/
syn match edited_wt /!!\s*wikitext\/edited/
syn match php_html /!!\s*html\/php/
syn match parsoid_html /!!\s*html\/parsoid\([+a-z]*\)/
syn match metadata /!!\s*metadata\(\/\([\/+a-z]\+\)\)\?/

hi default link comment Comment
hi default link article PreProc
hi default link test Statement
hi default link config Special
hi default link sections Statement
hi default link edited_wt Type
hi default link php_html Type
hi default link parsoid_html Type
hi default link metadata Type

" README:
" If you want to override styles for any specific parser test syntax element,
" you can define highlight rules for them in your .vimrc
"
" Examples below:
" hi config guifg=red ctermfg=red cterm=italic,bold gui=italic,bold
" hi parsoid_html guifg=magenta ctermfg=magenta cterm=bold,italic gui=bold,italic

let b:current_syntax = "ptest"
