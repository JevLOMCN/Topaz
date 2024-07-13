export default function MobileEffectsTableSkeleton() {
  return (
    <div className="custom-scroll relative flex w-full grid-cols-2 flex-col gap-1 overflow-auto rounded-md bg-primary-600 p-1 sm:grid md:w-[43rem] xl:hidden">
      {[1, 2, 3, 4, 5, 6, 7, 8].map((index) => (
        <div
          key={index}
          className="flex items-center gap-4 rounded bg-primary-500/20 px-1 py-0.5 text-xs font-light text-white md:px-3 md:py-1.5 md:text-sm"
        >
          <span className="h-6 w-6 shrink-0 animate-pulse rounded-full bg-primary-400" />
          <span className="h-4 w-32 animate-pulse rounded-full bg-primary-400 md:h-5" />
          <span className="ml-auto h-4 w-10 animate-pulse rounded-full bg-primary-400 md:h-5" />
        </div>
      ))}
    </div>
  )
}
