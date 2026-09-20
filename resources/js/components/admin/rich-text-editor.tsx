import { Toggle } from '@/components/ui/toggle';
import { cn } from '@/lib/utils';
import Placeholder from '@tiptap/extension-placeholder';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Heading2, Heading3, Italic, Link as LinkIcon, List, ListOrdered, Quote, Underline } from 'lucide-react';
import { useEffect, useRef } from 'react';

interface RichTextEditorProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    id?: string;
}

export function RichTextEditor({ value, onChange, placeholder = 'Write the page…', id }: RichTextEditorProps) {
    const lastEmitted = useRef(value || '');
    const editor = useEditor({
        immediatelyRender: false,
        shouldRerenderOnTransaction: true,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
                code: false,
                codeBlock: false,
                link: { openOnClick: false, autolink: true },
            }),
            Placeholder.configure({ placeholder }),
        ],
        content: value || '',
        editorProps: {
            attributes: {
                id: id ?? 'rich-text',
                class: 'page-content min-h-64 px-3 py-3 focus:outline-hidden',
            },
        },
        onUpdate: ({ editor: instance }) => {
            const html = instance.getHTML();
            lastEmitted.current = html;
            onChange(html);
        },
    });

    useEffect(() => {
        if (!editor) {
            return;
        }

        const next = value || '';

        if (next !== lastEmitted.current) {
            editor.commands.setContent(next, { emitUpdate: false });
            lastEmitted.current = next;
        }
    }, [editor, value]);

    if (!editor) {
        return <div className="min-h-64 rounded-md border border-neutral-300 dark:border-neutral-700" />;
    }

    const setLink = (): void => {
        const previous = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Link URL', previous ?? 'https://');

        if (url === null) {
            return;
        }

        if (url.trim() === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
    };

    return (
        <div className="overflow-hidden rounded-md border border-neutral-300 dark:border-neutral-700">
            <div className="flex flex-wrap gap-1 border-b border-neutral-200 bg-neutral-50 p-1 dark:border-neutral-800 dark:bg-neutral-900">
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('bold')}
                    onPressedChange={() => editor.chain().focus().toggleBold().run()}
                    aria-label="Bold"
                >
                    <Bold />
                </Toggle>
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('italic')}
                    onPressedChange={() => editor.chain().focus().toggleItalic().run()}
                    aria-label="Italic"
                >
                    <Italic />
                </Toggle>
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('underline')}
                    onPressedChange={() => editor.chain().focus().toggleUnderline().run()}
                    aria-label="Underline"
                >
                    <Underline />
                </Toggle>
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('heading', { level: 2 })}
                    onPressedChange={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
                    aria-label="Heading"
                >
                    <Heading2 />
                </Toggle>
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('heading', { level: 3 })}
                    onPressedChange={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
                    aria-label="Subheading"
                >
                    <Heading3 />
                </Toggle>
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('bulletList')}
                    onPressedChange={() => editor.chain().focus().toggleBulletList().run()}
                    aria-label="Bullet list"
                >
                    <List />
                </Toggle>
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('orderedList')}
                    onPressedChange={() => editor.chain().focus().toggleOrderedList().run()}
                    aria-label="Numbered list"
                >
                    <ListOrdered />
                </Toggle>
                <Toggle
                    type="button"
                    size="sm"
                    pressed={editor.isActive('blockquote')}
                    onPressedChange={() => editor.chain().focus().toggleBlockquote().run()}
                    aria-label="Quote"
                >
                    <Quote />
                </Toggle>
                <Toggle type="button" size="sm" pressed={editor.isActive('link')} onPressedChange={setLink} aria-label="Link">
                    <LinkIcon />
                </Toggle>
            </div>
            <EditorContent editor={editor} className={cn('bg-background')} />
        </div>
    );
}
