#!/bin/bash
REPEAT=10
GIT_STYLE=
GIT_HASH=
FORCE=
OUTPUT="wt2html-time"
PAGENAME="Barack Obama"

# check dependencies
if [ ! -x /usr/bin/time ]; then
    echo "You need to install GNU time (http://www.gnu.org/software/time)."
    echo "$ sudo apt-get install time"
    exit 1
fi
if [ ! -x /usr/bin/bc ]; then
    echo "You need to install GNU bc (http://ftp.gnu.org/gnu/bc/)."
    echo "$ sudo apt-get install bc"
    exit 1
fi

# parse options
while [[ "$#" -gt 0 ]]; do
    key="$1"
    case "$key" in
        -f|--force)
            FORCE=1
            ;;
        -g|--git)
            GIT_STYLE=1
            ;;
        -g=*|--git=*)
            GIT_STYLE=1
            GIT_HASH="${key#*=}"
            ;;
        -n|--repeat)
            shift
            REPEAT="$1"
            ;;
        -n=*|--repeat=*)
            REPEAT="${key#*=}"
            ;;
        -o)
            shift
            OUTPUT="$1"
            ;;
        -o=*|--output=*)
            OUTPUT="${key#*=}"
            ;;
        -p)
            shift
            PAGENAME="$1"
            ;;
        -p=*|--page=*)
            PAGENAME="${key#*=}"
            ;;
        -*)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
        *)
            OUTPUT="$key"
            ;;
    esac
    shift # Shift to get the next option
done

# page name should have spaces replaced with underscores
PAGENAME=$(echo -n "$PAGENAME" | sed -e 's/ /_/g')

# decorate with git commit info
log=''
if [ -n "$GIT_STYLE" ]; then
    log="$(git log --oneline -n 1 --no-decorate)"
    hash=$(echo "$log" | sed -e 's/ .*//')
    # count commits since GIT_HASH
    if [ -n "$GIT_HASH" ]; then
        count=$(printf '%03d' $(git rev-list "$GIT_HASH"..."$hash" | wc -l))
        OUTPUT="$OUTPUT-$count"
    fi
    OUTPUT="$OUTPUT-$hash"
fi

# Pre-record network traffic, if necessary.
if [ -n "$FORCE" -o ! -f $(dirname $0)/../nocks/en.wikipedia.org/"$PAGENAME.js" ]; then
    echo "Recording parse network traffic."
    $(dirname $0)/../bin/parse.js --wt2html --domain en.wikipedia.org \
        --pageName $PAGENAME --record < /dev/null > /dev/null
fi

# only re-run benchmark if output file doesn't exist.  otherwise, just
# recompute average
if [ -n "$FORCE" -o ! -f "$OUTPUT.times" ]; then
    rm -f "$OUTPUT.times"
    for f in $(seq 1 $REPEAT) extra1 extra2; do
        /usr/bin/time -f%U -o "$OUTPUT.times" -a \
        $(dirname $0)/../bin/parse.js --wt2html --domain en.wikipedia.org \
        --pageName $PAGENAME --replay < /dev/null > /dev/null
    done
fi

# now summarize
avg=$(echo "scale=3;(" $(for f in $(sort -n "$OUTPUT.times" | head -n-1 | tail -$REPEAT) ; do echo -n $f + ; done) "0)/$REPEAT" | bc)

if [ -n "$GIT_STYLE" ]; then
    echo $avg $log
else
    echo $avg
fi
