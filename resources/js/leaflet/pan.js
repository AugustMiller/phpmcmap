export default L.Control.extend({
    map: null,

    onAdd: function(map) {
        this.map = map;
        const $container = L.DomUtil.create('div');

        const buttonMap = [
            { label: 'Up', handle: 'up', symbol: '↑' },
            { label: 'Down', handle: 'down', symbol: '↓' },
            { label: 'Left', handle: 'left', symbol: '←' },
            { label: 'Right', handle: 'right', symbol: '→' },
        ];

        buttonMap.forEach((b) => {
            const $button = L.DomUtil.create('button', `pan-arrow pan-arrow--${b.handle}`, $container);
            $button.title = b.label;
            $button.innerText = b.symbol;

            $button.addEventListener('click', this.handlePan.bind(this, b.handle));
        });

        $container.style = `padding: 10px; background-color: white; border-radius: 2px;`;

        return $container;
    },

    handlePan: function(dir, e) {
        const dirs = {
            up: -100,
            down: 100,
            left: -100,
            right: 100,
        };

        const center = this.map.getCenter();

        console.log(dir, e);
    },

    onRemove: function(map) {
        this.map = null;
    },

    _getNearestTileCenter: function(coordinates) {
        
    },
});
