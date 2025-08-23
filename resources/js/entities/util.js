
export function createSymbolMarker(entity, title) {
    if (!title) {
        title = cleanEntityId(entity.entity_type);
    }

    const marker = L.marker([-entity.z, entity.x], {
        title: title,
        icon: createSymbolIcon(cleanEntityId(entity.entity_type)),
    });

    marker.bindPopup(`${title}<br>${entity.x}, ${entity.z} (Elevation: ${entity.y})`);

    return marker;
};

export function createSymbolIcon(sym) {
    return L.divIcon({
        className: `poi poi--entity-${sym}`,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
    });
};

export function cleanEntityId(id) {
    return id.replace('minecraft:', '');
};
