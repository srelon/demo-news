export interface AdminCommentUser {
    id: number
    name: string
    username: string | null
    img: string | null
    is_moderator?: boolean
}

export interface AdminCommentArticle {
    id: number
    title: string
    image: string | null
    url: string | null
}

export interface ArticleComment {
    id: number
    article_id: number
    body: string
    status: number
    deleted_by?: number | null
    parent_id: number | null
    replied_to_comment_id?: number | null
    replied_to?: {
        id: number
        user: {
            name: string
            username: string | null
        }
    } | null
    likes: number
    dislikes: number
    user_reaction: number
    replies_count: number
    reports_count: number
    user: AdminCommentUser | null
    created_at: string
}

/**
 * Shared state and actions provided by ArticleComments to the recursive
 * ArticleCommentItem tree (injected as a reactive object, refs unwrapped).
 */
export interface ArticleCommentsCtx {
    highlighted_id: number | null
    edit_comment: ArticleComment | null
    edit_body: string
    edit_loading: boolean
    reply_to: ArticleComment | null
    reply_body: string
    reply_loading: boolean
    expanded_replies: Record<number, ArticleComment[]>
    replies_loading: Record<number, boolean>
    like_loading: Record<number, boolean>
    restore_loading: Record<number, boolean>
    likeComment: (comment: ArticleComment, opp_type: 1 | 2) => void
    deleteComment: (comment: ArticleComment) => void
    restoreComment: (comment: ArticleComment) => void
    startEdit: (comment: ArticleComment) => void
    cancelEdit: () => void
    submitEdit: (comment: ArticleComment) => void
    openReply: (comment: ArticleComment) => void
    cancelReply: () => void
    submitReply: () => void
    openReports: (comment: ArticleComment) => void
    loadReplies: (comment: ArticleComment) => void
}

export interface AdminComment {
    id: number
    parent_id: number | null
    body: string
    status: number
    deleted_by?: number | null
    reports_count: number
    likes: number
    dislikes: number
    user: AdminCommentUser | null
    article: AdminCommentArticle | null
    created_at: string
}
