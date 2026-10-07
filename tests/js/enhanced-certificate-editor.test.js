import test from 'node:test';
import assert from 'node:assert/strict';
import {
    calculateDraggedRect,
    calculateResizedRect,
    constrainRect,
} from '../../resources/js/enhanced-certificate-editor.js';

test('drag converts screen delta using the gesture zoom', () => {
    const rect = { x: 100, y: 100, width: 200, height: 40 };

    assert.equal(calculateDraggedRect(rect, 20, 0, 0.5, false).x, 140);
    assert.equal(calculateDraggedRect(rect, 20, 0, 1, false).x, 120);
    assert.equal(calculateDraggedRect(rect, 20, 0, 2, false).x, 110);
});

test('small drag deltas accumulate before snapping the absolute position', () => {
    const rect = { x: 53, y: 50, width: 200, height: 40 };

    assert.equal(calculateDraggedRect(rect, 2, 0, 1, true).x, 60);
    assert.equal(calculateDraggedRect(rect, 8, 0, 1, true).x, 60);
});

test('left resize preserves the opposite edge and minimum size', () => {
    const rect = { x: 100, y: 100, width: 200, height: 40 };
    const resized = calculateResizedRect(rect, { left: 40 }, 2, false);
    const minimum = calculateResizedRect(rect, { left: 500 }, 1, false);

    assert.deepEqual(resized, { x: 120, y: 100, width: 180, height: 40 });
    assert.equal(minimum.x + minimum.width, 300);
    assert.equal(minimum.width, 20);
});

test('constraints keep duplicated rectangles inside the canvas', () => {
    assert.deepEqual(
        constrainRect({ x: 1120, y: 790, width: 200, height: 40 }),
        { x: 923, y: 754, width: 200, height: 40 },
    );
});
