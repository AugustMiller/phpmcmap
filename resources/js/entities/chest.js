import { createSymbolIcon } from './util';

export default function createChestMarker(entity) {
    const total = entity.metadata.Items.reduce(function(total, slot) {
        return total + slot.Count;
    }, 0);

    const marker = L.marker([-entity.z, entity.x], {
        title: `Chest`,
        icon: createSymbolIcon('chest'),
    });

    marker.bindPopup(`Chest: ${total} item(s)`);

    return marker;
};
