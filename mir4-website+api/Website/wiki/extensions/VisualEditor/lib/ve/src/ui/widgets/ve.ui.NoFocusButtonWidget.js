/**
 * Button widget that cancels mousedown events.
 *
 * TODO: Make cancelButtonMouseDownEvents an upstream param,
 * instead of requiring inheritance.
 */
ve.ui.NoFocusButtonWidget = function NoFocusButtonWidget() {
	ve.ui.NoFocusButtonWidget.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.NoFocusButtonWidget, OO.ui.ButtonWidget );
ve.ui.NoFocusButtonWidget.static.cancelButtonMouseDownEvents = true;
