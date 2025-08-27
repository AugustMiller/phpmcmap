const CHUNK_WIDTH = 512;

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
            up: L.point(0, -1),
            down: L.point(0, 1),
            left: L.point(-1, 0),
            right: L.point(1, 0),
        };

        const center = this.map.getCenter();
        const regionCenter = L.point(
            (center.lat - (center.lat % CHUNK_WIDTH)) / Math.pow(2, 8),
            (center.lng - (center.lng % CHUNK_WIDTH)) / Math.pow(2, 8),
        );
        const targetCenter = regionCenter.add(dirs[dir]);

        this.map.panTo(L.latLng(targetCenter.multiplyBy(CHUNK_WIDTH)));
    },

    onRemove: function(map) {
        this.map = null;
    },

    _getNearestTileCenter: function(coordinates) {
        
    },
});
