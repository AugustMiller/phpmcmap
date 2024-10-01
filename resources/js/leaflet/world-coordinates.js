export default L.Control.extend({
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
        const worldX = e.latlng.lng;
        const worldZ = -e.latlng.lat;

        this.$block.innerText = `Block: ${Math.floor(worldX)}, ${Math.floor(worldZ)}`;
        this.$chunk.innerText = `Chunk: ${Math.floor(worldX / 16)}, ${Math.floor(worldZ / 16)}`;
        this.$region.innerText = `Region: ${Math.floor(worldX / 16 / 32)}, ${Math.floor(worldZ / 16 / 32)}`;
    },

    onMapMouseOut: function(e) {
        this.getContainer().style.visibility = 'hidden';
    },
});
