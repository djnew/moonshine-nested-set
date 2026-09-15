import Sortable from "sortablejs";

const sortableGroups = new Map();
const pendingGroups = new Set();
const pendingEmptyChildTargets = new Map();

const booleanOption = (value, fallback = false) => {
    if (value === undefined) {
        return fallback;
    }

    return value === true || value === "true";
};

const groupKey = (group) => group || "__moonshine_nestedset_default__";

const groupInstances = (group) => {
    const key = groupKey(group);

    if (!sortableGroups.has(key)) {
        sortableGroups.set(key, new Set());
    }

    return sortableGroups.get(key);
};

const setGroupSaving = (group, saving) => {
    const key = groupKey(group);

    if (saving) {
        pendingGroups.add(key);
    } else {
        pendingGroups.delete(key);
    }

    groupInstances(group).forEach((sortable) => {
        sortable.option("disabled", saving);
        sortable.el.classList.toggle("nested-set--saving", saving);
        sortable.el.setAttribute("aria-busy", saving ? "true" : "false");
    });
};

export const showToast = (text, type) => {
    if (window.MoonShine?.ui?.toast) {
        window.MoonShine.ui.toast(text, type);

        return;
    }

    window.dispatchEvent(
        new CustomEvent("toast", {
            detail: { text, type, duration: null },
        }),
    );
};

export const responseMessage = (error, fallback) => {
    const message = error?.response?.data?.message;

    return typeof message === "string" && message.trim() !== ""
        ? message
        : fallback;
};

export const restoreItem = (event) => {
    if (!event.from || !event.item) {
        return;
    }

    event.item.remove();
    const reference = event.from.children[event.oldIndex] ?? null;
    event.from.insertBefore(event.item, reference);
};

const rememberEmptyChildTarget = (group, event, draggedItem) => {
    const key = groupKey(group);

    if (!event || !draggedItem) {
        pendingEmptyChildTargets.delete(key);

        return;
    }

    const pointer = event.touches?.[0] ?? event.changedTouches?.[0] ?? event;
    const target = document
        .elementFromPoint(pointer.clientX, pointer.clientY)
        ?.closest("[data-nested-set-list]");

    if (
        target?.dataset?.id &&
        target.children.length === 0 &&
        !draggedItem.contains(target)
    ) {
        pendingEmptyChildTargets.set(key, target);

        return;
    }

    pendingEmptyChildTargets.delete(key);
};

export default (url = null, group = null, element = null) => ({
    sortable: null,

    init(onSort = null) {
        const el = element || this.$el;
        const options = {
            group: group
                ? {
                      name: group,
                  }
                : null,
            handle: el.dataset.handle || undefined,
            animation: Number(el.dataset.animation || 150),
            fallbackOnBody: booleanOption(el.dataset.fallbackOnBody, true),
            swapThreshold: Number(el.dataset.swapThreshold || 0.65),
            emptyInsertThreshold: Number(el.dataset.emptyInsertThreshold || 16),
            ghostClass: "nested-element--ghost",
            chosenClass: "nested-element--chosen",
            dragClass: "nested-element--drag",
            onStart: () => pendingEmptyChildTargets.delete(groupKey(group)),
            onMove: (event, originalEvent) => {
                rememberEmptyChildTarget(group, originalEvent, event.dragged);

                return !pendingGroups.has(groupKey(group));
            },

            onEnd: async (evt) => {
                const emptyChildTarget = pendingEmptyChildTargets.get(
                    groupKey(group),
                );
                pendingEmptyChildTargets.delete(groupKey(group));
                let destination = evt.to;
                let newIndex = evt.newIndex;

                if (
                    emptyChildTarget?.isConnected &&
                    emptyChildTarget !== evt.to
                ) {
                    emptyChildTarget.append(evt.item);
                    destination = emptyChildTarget;
                    newIndex = 0;
                }

                if (url && evt.item?.dataset?.id) {
                    const formData = new FormData();
                    const ids = Array.from(destination.children)
                        .map((item) => item.dataset?.id)
                        .filter(Boolean);

                    formData.append("id", evt.item.dataset.id);
                    formData.append("parent", destination.dataset?.id ?? "");
                    formData.append("index", newIndex);
                    formData.append("data", ids.join(","));

                    evt.item.classList.add("nested-element--saving");
                    setGroupSaving(group, true);

                    try {
                        await axios.post(url, formData);
                        showToast(
                            el.dataset.successMessage || "Order saved",
                            "success",
                        );

                        if (evt.item.dataset.fragmentEvent) {
                            window.dispatchEvent(
                                new Event(evt.item.dataset.fragmentEvent),
                            );
                        }
                    } catch (error) {
                        restoreItem(evt);
                        showToast(
                            responseMessage(
                                error,
                                el.dataset.errorMessage ||
                                    "Unable to save order",
                            ),
                            "error",
                        );
                    } finally {
                        evt.item.classList.remove("nested-element--saving");
                        setGroupSaving(group, false);
                    }
                }

                if (typeof onSort === "function") {
                    onSort(evt);
                }
            },
        };

        this.sortable = Sortable.create(el, options);
        groupInstances(group).add(this.sortable);
    },

    destroy() {
        if (!this.sortable) {
            return;
        }

        groupInstances(group).delete(this.sortable);
        this.sortable.destroy();
        this.sortable = null;
    },
});
