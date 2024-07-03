<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax;

/**
 * Events emitter class. An instance of this class is used by Parser and Scanner
 * to emit events and attach listeners to them
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class EventsEmitter
{
    /**
     * Events registry array
     *
     * @var array
     */
    protected $eventsRegistry = array();
    
    /**
     * Attaches a listener function to the given event
     * 
     * @param string    $event      Event name
     * @param callable  $listener   Listener function
     * 
     * @return $this
     */
    public function addListener($event, $listener)
    {
        if (!isset($this->eventsRegistry[$event])) {
            $this->eventsRegistry[$event] = array();
        }
        $this->eventsRegistry[$event][] = $listener;
        return $this;
    }
    
    /**
     * Fires an event
     * 
     * @param string    $event  Event name
     * @param array     $args   Arguments to pass to functions attached to the
     *                          event
     * 
     * @return $this
     */
    public function fire($event, $args = array())
    {
        if (isset($this->eventsRegistry[$event])) {
            foreach ($this->eventsRegistry[$event] as $listener) {
                call_user_func_array($listener, $args);
            }
        }
        return $this;
    }
}