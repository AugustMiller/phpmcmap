import { cleanEntityId, createSymbolIcon } from './util';

export default function createChestMarker(entity) {
    if (typeof entity.metadata.Items === 'undefined') {
        throw new Error('Not enough metadata present to render.');
    }

    const type = cleanEntityId(entity.entity_type);

    const total = entity.metadata.Items.reduce(function(total, slot) {
        const count = slot.count || slot.Count;

        return total + count;
    }, 0);

    const marker = L.marker([-entity.z, entity.x], {
        title: `Container (${type})`,
        icon: createSymbolIcon('container'),
    });

    const message = total === 0 ? `Empty container (${type})` : `Container (${type}): ${total} item(s)`;

    marker.bindPopup(message);

    return marker;
};
