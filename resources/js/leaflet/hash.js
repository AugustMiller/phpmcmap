/**
 * Prefix for hash location params.
 */
const HASH_PREFIX = '#pos:';

/**
 * Parse a hash/fragment into a map position and zoom level.
 * 
 * @param {String} hash `window.location.hash`, in a format like `#pos:lng,lat,zoom`
 * @return {Object}
 */
export function parseHash(hash) {
    if (!hash) {
        return;
    }

    // Remove prefix:
    hash = hash.replace(HASH_PREFIX, '');

    // Split into components:
    const components = hash.split(',').map(i => parseInt(i));

    if (components.length !== 3) {
        return;
    }

    const [lng, lat, zoom] = components;

    return { lng, lat, zoom };
};

export function createHash(map) {
    const coordinates = [
        Math.round(map.getCenter().lng),
        Math.round(map.getCenter().lat),
        map.getZoom(),
    ];

    return `${HASH_PREFIX}${coordinates.join(',')}`
};