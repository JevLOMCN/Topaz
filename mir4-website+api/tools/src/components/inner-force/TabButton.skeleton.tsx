export default function TabButtonSkeleton() {
  return (
    <button
      className={
        'flex h-20 w-20 shrink-0 flex-col justify-center rounded border-2 border-transparent bg-primary-600/50 px-3 py-2 text-white transition-colors hover:border-primary-400 hover:bg-primary-600/80 sm:h-28 sm:w-28 xl:h-20 xl:w-52 xl:flex-row xl:items-center xl:justify-between'
      }
    >
      <div className="h-10 w-10 animate-pulse rounded-full bg-primary-400 sm:h-[3.75rem] sm:w-[3.75rem]" />
      <div className="hidden flex-col items-end justify-between text-xs font-medium xl:flex xl:h-full">
        <span className="flex h-4 w-12 animate-pulse rounded-full bg-primary-400"></span>
        <span className="flex h-4 w-24 animate-pulse rounded-full bg-primary-400"></span>
      </div>
    </button>
  )
}
