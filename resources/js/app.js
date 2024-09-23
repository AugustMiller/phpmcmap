const $container = document.getElementById('map');
const map = L.map($container, {
    crs: L.CRS.Simple,
})
    .setView([0, 0], 2);

L.tileLayer('/api/tiles/{z}/{x}/{y}', {
    minZoom: 0,
    maxZoom: 4,
    noWrap: true,
    updateWhenZooming: false,
    updateInterval: 200,
    keepBuffer: 4,
}).addTo(map);

// Add a home marker:
L.marker([0, 0]).addTo(map);

// Set up our coordinate output:
L.Control.WorldCoordinates = L.Control.extend({
    map: null,
    $block: null,
    $chunk: null,
    $region: null,

    _bindings: {},

    onAdd: function(map) {
        this.map = map;
        const $container = L.DomUtil.create('div');

        this.$block = L.DomUtil.create('div');
        this.$chunk = L.DomUtil.create('div');
        this.$region = L.DomUtil.create('div');

        $container.appendChild(this.$block);
        $container.appendChild(this.$chunk);
        $container.appendChild(this.$region);

        $container.style = `padding: 10px; background-color: white; min-width: 15em; border-radius: 2px;`;
        $container.style.visibility = 'hidden';

        map.on('mousemove', this._bindings.onMouseMove = this.onMapMouseMove.bind(this));
        map.on('mouseover', this._bindings.onMapMouseOver = this.onMapMouseOver.bind(this));
        map.on('mouseout', this._bindings.onMapMouseOut = this.onMapMouseOut.bind(this));

        return $container;
    },

    onRemove: function(map) {
        map.off('mousemove', this._bindings.onMapMouseMove);
        map.off('mouseover', this._bindings.onMapMouseOver);
        map.off('mouseout', this._bindings.onMapMouseOut);

        this.map = null;
    },

    onMapMouseOver: function(e) {
        this.getContainer().style.visibility = 'visible';
    },

    onMapMouseMove: function(e) {
        const dimensions = map.getSize();
        const blockScale = Math.pow(2, this.map.getZoom() - 1);

        const normalizedMouseX = e.layerPoint.x - (dimensions.x / 2);
        const normalizedMouseY = e.layerPoint.y - (dimensions.y / 2);

        const worldX = normalizedMouseX / blockScale;
        const worldZ = normalizedMouseY / blockScale;

        this.$block.innerText = `Block: ${Math.floor(worldX)}, ${Math.floor(worldZ)}`;
        this.$chunk.innerText = `Chunk: ${Math.floor(worldX / 16)}, ${Math.floor(worldZ / 16)}`;
        this.$region.innerText = `Region: ${Math.floor(worldX / 16 / 32)}, ${Math.floor(worldZ / 16 / 32)}`;
    },

    onMapMouseOut: function(e) {
        this.getContainer().style.visibility = 'hidden';
    },
});

(new L.Control.WorldCoordinates({ position: 'bottomleft' })).addTo(map);

// Add markers for players:
fetch('/api/players')
    .then((r) => r.json())
    .then((players) => {
        for (let p in players) {
            const player = players[p];

            // Ignore anyone who isn't in the overworld:
            if (player.dimension !== 'minecraft:overworld') {
                continue;
            }

            // Player positions are provided as [X, Y, Z] (Lng, El, Lat)

            L.marker([player.position[2] / 8, player.position[0] / 8], {
                icon: L.divIcon({
                    className: 'player',
                    html: player.name,
                }),
            }).addTo(map);
        }
    });
