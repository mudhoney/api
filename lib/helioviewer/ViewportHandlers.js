/*global Class, Object, document, window, Event, $ */
var ViewportHandlers = Class.create({
     startingPosition: { x: 0, y: 0 },
     mouseStartingPosition: { x: 0, y: 0 },
     mouseCurrentPosition: { x: 0, y: 0 },
     moveCounter: 0,
     moveThrottle: 2,

     initialize: function (viewport) {
          this.viewport = viewport;
          this.bMouseMove = this.mouseMove.bindAsEventListener(this);
          this.bMouseDown = this.mouseDown.bindAsEventListener(this);
          this.bMouseUp = this.mouseUp.bindAsEventListener(this);

          Event.observe(window, 'mousemove', this.bMouseMove);
          Event.observe(document, 'mousemove', this.bMouseMove);
          Event.observe(this.viewport.domNode, 'mousedown', this.bMouseDown);
          Event.observe(window, 'mouseup', this.bMouseUp);
          Event.observe(document, 'mouseup', this.bMouseUp);
     },

     mouseDown: function (event) {
          //this.viewport.output('down');
          this.viewport.isMoving = true;
          this.startingPosition = this.viewport.currentPosition;
          this.mouseStartingPosition = {
               x: Event.pointerX(event), 
               y: Event.pointerY(event)
          };
          this.viewport.domNode.setStyle({ cursor: 'all-scroll' });
          if (this.viewport.domNode.setCapture) {
               this.viewport.domNode.setCapture();
          }
          this.viewport.startMoving();
     },
     
     mouseUp: function (event) {
          //this.viewport.output('up');
          this.viewport.isMoving = false;
          this.viewport.domNode.setStyle({ cursor: 'pointer' });
          if (this.viewport.domNode.releaseCapture) {
               this.viewport.domNode.releaseCapture();
          }
          this.viewport.endMoving();
     },
     
    mouseMove: function (event) {
        //this.viewport.output('move');
        if (!this.viewport.isMoving) {
        	return;
        }
        this.moveCounter = (this.moveCounter + 1) % this.moveThrottle;
        if (this.moveCounter !== 0) {
        	return;
        }
          
        this.mouseCurrentPosition = {
        	x: Event.pointerX(event), 
            y: Event.pointerY(event)
         };
          
        this.viewport.moveBy(
	   	    this.mouseStartingPosition.x - this.mouseCurrentPosition.x,
            this.mouseStartingPosition.y - this.mouseCurrentPosition.y
		);
    /*
    this.viewport.moveTo(
      this.startingPosition.x + this.mouseStartingPosition.x - this.mouseCurrentPosition.x,
      this.startingPosition.y + this.mouseStartingPosition.y - this.mouseCurrentPosition.y
    );
    */
	}
});