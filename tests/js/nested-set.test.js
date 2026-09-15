import assert from "node:assert/strict";
import test from "node:test";

import {
    responseMessage,
    restoreItem,
    showToast,
} from "../../resources/js/plugins/nested-set.js";

class FakeItem {
    constructor(id) {
        this.id = id;
        this.parent = null;
    }

    remove() {
        if (!this.parent) {
            return;
        }

        this.parent.children = this.parent.children.filter(
            (item) => item !== this,
        );
        this.parent = null;
    }
}

class FakeList {
    constructor(...items) {
        this.children = items;
        items.forEach((item) => {
            item.parent = this;
        });
    }

    insertBefore(item, reference) {
        item.remove();
        const index = reference
            ? this.children.indexOf(reference)
            : this.children.length;
        this.children.splice(index, 0, item);
        item.parent = this;
    }

    ids() {
        return this.children.map((item) => item.id);
    }
}

test("restores an item to its old index in the same list", () => {
    const first = new FakeItem(1);
    const second = new FakeItem(2);
    const third = new FakeItem(3);
    const list = new FakeList(third, first, second);

    restoreItem({ from: list, item: third, oldIndex: 2 });

    assert.deepEqual(list.ids(), [1, 2, 3]);
});

test("restores an item after a failed cross-list move", () => {
    const first = new FakeItem(1);
    const moved = new FakeItem(2);
    const third = new FakeItem(3);
    const source = new FakeList(first, third);
    const target = new FakeList(moved);

    restoreItem({ from: source, item: moved, oldIndex: 1 });

    assert.deepEqual(source.ids(), [1, 2, 3]);
    assert.deepEqual(target.ids(), []);
});

test("uses the server error message when it is available", () => {
    assert.equal(
        responseMessage(
            { response: { data: { message: "Server rejected the move" } } },
            "Fallback",
        ),
        "Server rejected the move",
    );
    assert.equal(
        responseMessage(new Error("Network error"), "Fallback"),
        "Fallback",
    );
});

test("shows notifications through the MoonShine toast API", () => {
    const calls = [];
    global.window = {
        MoonShine: {
            ui: {
                toast: (...arguments_) => calls.push(arguments_),
            },
        },
    };

    showToast("Order saved", "success");

    assert.deepEqual(calls, [["Order saved", "success"]]);
    delete global.window;
});
