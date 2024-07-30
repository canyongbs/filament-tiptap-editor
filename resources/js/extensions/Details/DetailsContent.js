import { Node, mergeAttributes, findParentNode, defaultBlockAt } from '@tiptap/core';
import { Selection } from 'prosemirror-state';

export const DetailsContent = Node.create({
    name: 'detailsContent',

    content: 'block+',

    defining: true,

    selectable: false,

    addOptions() {
        return {
            HTMLAttributes: {},
        };
    },

    parseHTML() {
        return [
            {
                tag: `div[data-type="details-content"]`,
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, { 'data-type': 'details-content' }),
            0,
        ];
    },

    addKeyboardShortcuts() {
        return {
            // allows double enter to exit content node
            Enter: ({ editor }) => {
                const { state, view } = editor;
                const { selection } = state;
                const { $from, empty } = selection;
                const detailsContent = findParentNode((node) => node.type === this.type)(selection);

                if (!empty || !detailsContent || !detailsContent.node.childCount) {
                    return false;
                }

                const fromIndex = $from.index(detailsContent.depth);
                const { childCount } = detailsContent.node;
                const isAtEnd = childCount === fromIndex + 1;

                if (!isAtEnd) {
                    return false;
                }

                const defaultChildType = detailsContent.node.type.contentMatch.defaultType;
                const defaultChildNode =
                    defaultChildType === null || defaultChildType === void 0
                        ? void 0
                        : defaultChildType.createAndFill();

                if (!defaultChildNode) {
                    return false;
                }

                const $childPos = state.doc.resolve(detailsContent.pos + 1);
                const lastChildIndex = childCount - 1;
                const lastChildNode = detailsContent.node.child(lastChildIndex);
                const lastChildPos = $childPos.posAtIndex(lastChildIndex, detailsContent.depth);
                const lastChildNodeIsEmpty = lastChildNode.eq(defaultChildNode);

                if (!lastChildNodeIsEmpty) {
                    return false;
                }

                const above = $from.node(-3);
                if (!above) {
                    return false;
                }

                const after = $from.indexAfter(-3);
                const type = defaultBlockAt(above.contentMatchAt(after));
                if (!type || !above.canReplaceWith(after, after, type)) {
                    return false;
                }

                const node = type.createAndFill();

                if (!node) {
                    return false;
                }

                const { tr } = state;
                const pos = $from.after(-2);
                tr.replaceWith(pos, pos, node);
                const $pos = tr.doc.resolve(pos);
                const newSelection = Selection.near($pos, 1);
                tr.setSelection(newSelection);
                const deleteFrom = lastChildPos;
                const deleteTo = lastChildPos + lastChildNode.nodeSize;
                tr.delete(deleteFrom, deleteTo);
                tr.scrollIntoView();
                view.dispatch(tr);
                return true;
            },
        };
    },
});
