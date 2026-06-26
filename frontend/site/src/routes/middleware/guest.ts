export default function guest({ next, store }: any) {
    if (store.user) {
        return next({ name: 'home' })
    }

    return next()
}
