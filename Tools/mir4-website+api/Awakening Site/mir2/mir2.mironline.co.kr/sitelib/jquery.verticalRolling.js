/*
 * vertical news ticker
 * Tadas Juozapaitis ( kasp3rito [eta] gmail (dot) com )
 * http://www.jugbit.com/jquery-vticker-vertical-news-ticker/
 * 
 * The following initialization options are supported:
 * 
 * speed          : speed of scrolling animation, in milliseconds (default 700)
 * interval       : wait time between scrolling, in milliseconds (default 4000)
 * items          : number of items to show at a time (default 2)
 * animation      : animation to be used for scrolling. To fade in/fade out use 'fade'
 * mousePause     : stop scrolling on mouse over (default true)
 * height         : set vTicker container height. Also disables all items auto height calculation. It is usable to remove gaps between items. (default 0 – it means off)
 * next           : jQuery selector for a DOM element that can be clicked for scrolling to the next item
 * previous       : jQuery selector for a DOM element that can be clicked for scrolling to the previous item
 * pause          : jQuery selector for a DOM element that can be clicked to pause (ongoing) scrolling
 * resume         : jQuery selector for a DOM element that can be clicked to resume (paused) scrolling
 * direction      : direction of scroll. Values are 'up' and 'down'. (default 'up')
 * isPaused       : whether the animation is paused when the plugin first loads (default false)
 * debug          : whether debugging messages should be printed (default false)
 * onChange       : callback function to call when a new item is scrolled into view
 * onPause        : callback function to call when the animation is paused
 * onResume       : callback function to call when the animation resumes
 */
(function($) {
    $.fn.vTicker = function(options) {
        var defaults = {
            animation : '',
            debug : false,
            direction : 'up',
            height : 0,
            interval : 4000,
            isPaused : false,
            items : 3,
            mousePause : true,
            next : null,
            pause : null,
            previous : null,
            resume : null,
            speed : 700,
            onChange : null,
            onPause : null,
            onResume : null,
			buttonWrap:'.news_control'
        };

        var options = $.extend(defaults, options, {
            isAnimating : false
        });

        moveUp = function(obj2, height, options, force) {
            if ((options.isPaused && !force) || options.isAnimating) {
                return;
            }

            // Set a flag to indicate that the animation is in progress.
            options.isAnimating = true;

            if (options.debug) {
                console.log("jquery.vTicker.moveUp called.");
            }

            var obj = obj2.children('ul');

            var clone = obj.children('li:first').clone(true);

            if (options.height > 0) {
                height = obj.children('li:first').height();
            }

            obj.animate({
                top : '-=' + height + 'px'
            }, options.speed, function() {
                $(this).children('li:first').remove();
                $(this).css('top', '0px');
            });

            if (options.animation == 'fade') {
                obj.children('li:first').fadeOut(options.speed);
                if (options.height == 0) {
                    obj.children('li:eq(' + options.items + ')').hide().fadeIn(options.speed).show();
                }
            }

            clone.appendTo(obj);

            if (typeof (options.onChange) === "function") {
                options.onChange(obj);
            }

            // Unset the flag to indicate that the animation has finished.
            options.isAnimating = false;
        };

        moveDown = function(obj2, height, options, force) {
            if ((options.isPaused && !force) || options.isAnimating) {
                return;
            }

            // Set a flag to indicate that the animation is in progress.
            options.isAnimating = true;

            if (options.debug) {
                console.log("jquery.vTicker.moveDown called.");
            }

            var obj = obj2.children('ul');

            var clone = obj.children('li:last').clone(true);

            if (options.height > 0) {
                height = obj.children('li:first').height();
            }

            obj.css('top', '-' + height + 'px').prepend(clone);

            obj.animate({
                top : 0
            }, options.speed, function() {
                $(this).children('li:last').remove();
            });

            if (options.animation == 'fade') {
                if (options.height == 0) {
                    obj.children('li:eq(' + options.items + ')').fadeOut(options.speed);
                }
                obj.children('li:first').hide().fadeIn(options.speed).show();
            }

            if (typeof (options.onChange) === "function") {
                options.onChange(obj);
            }

            // Unset the flag to indicate that the animation has finished.
            options.isAnimating = false;
        };

        return this.each(function() {
            var obj = $(this);
            var maxHeight = 0;

            obj.css({
                overflow : 'hidden',
                position : 'relative'
            }).children('ul').css({
                position : 'absolute',
                margin : 0,
                padding : 0
            }).children('li').css({
                margin : 0,
                padding : 0
            });

            if (options.height == 0) {
                obj.children('ul').children('li').each(function() {
                    if ($(this).height() > maxHeight) {
                        maxHeight = $(this).height();
                    }
                });

                obj.children('ul').children('li').each(function() {
                    $(this).height(maxHeight);
                });

                obj.height(maxHeight * options.items);
            } else {
                obj.height(options.height);
            }

            // button on mouseover
            if (typeof (options.buttonWrap) != "undefined" && options.buttonWrap != null) {
                $(options.buttonWrap).mouseover(function(e) {
					options.isPaused=true;
                }).mouseleave(function(e) {
					options.isPaused=false;
				});
            }

            // Next Button
            if (typeof (options.next) != "undefined" && options.next != null) {
                $(options.next).click(function() {
                    if (options.direction == 'up') {
                        moveUp(obj, maxHeight, options, true);
                    } else {
						console.log('test');
                        moveDown(obj, maxHeight, options, true);
                    }
                });
            }

            // Previous Button
            if (typeof (options.previous) != "undefined" && options.previous != null) {
                $(options.previous).click(function() {
                  if (options.direction == 'up') {
                        moveDown(obj, maxHeight, options, true);
                    } else {
                        moveUp(obj, maxHeight, options, true);
                    }
                });
            }

            // Pause Button
            if (typeof (options.pause) != "undefined" && options.pause != null) {
                $(options.pause).click(function() {
                    options.isPaused = true;

                    if (options.debug) {
                        console.log("jQuery.vTicker paused.");
                    }

                    if (typeof (options.onPause) === "function") {
                        options.onPause(obj);
                    }
                });
            }

            // Resume Button
            if (typeof (options.resume) != "undefined" && options.resume != null) {
                $(options.resume).click(function() {
                    options.isPaused = false;

                    if (options.debug) {
                        console.log("jQuery.vTicker resumed.");
                    }

                    if (typeof (options.onResume) === "function") {
                        options.onResume(obj);
                    }
                });
            }

            var interval = setInterval(function() {
                if (options.direction == 'up') {
                    moveUp(obj, maxHeight, options, false);
                } else {
                    moveDown(obj, maxHeight, options, false);
                }
            }, options.interval);

            if (options.mousePause) {
                obj.bind("mouseenter", function() {
                    options.isPaused = true;

                    if (options.debug) {
                        console.log("jQuery.vTicker paused due to mouse over.");
                    }
                }).bind("mouseleave", function() {
                    options.isPaused = false;

                    if (options.debug) {
                        console.log("jQuery.vTicker resumed due to mouse out.");
                    }
                });
            }
        });
    };
})(jQuery);