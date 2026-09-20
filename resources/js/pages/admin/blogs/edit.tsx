import { BlogEditor } from '@/pages/admin/blogs/index';
import { type BlogPostRecord, type BlogRecord, type Paginated, type SelectOption } from '@/types';

interface EditBlogProps {
    blog: { data: BlogRecord };
    posts: Paginated<BlogPostRecord>;
    categories: { id: number; name: string }[];
    statuses: SelectOption[];
}

export default function EditBlog({ blog, posts, categories, statuses }: EditBlogProps) {
    return <BlogEditor blog={blog.data} posts={posts} categories={categories} statuses={statuses} />;
}
