export default function DesktopEffectsTableSkeleton() {
  return (
    <div className="custom-scroll relative hidden max-h-[calc(100vh-10rem)] w-[23rem] flex-col gap-1 overflow-auto rounded-md bg-primary-600 p-1 md:rounded-xl xl:flex">
      {[1, 2, 3, 4, 5, 6, 7, 8].map((index) => (
        <div
          key={index}
          className="flex items-center gap-4 rounded bg-primary-500/20 px-1 py-0.5 text-xs font-light text-white md:px-3 md:py-1.5 md:text-sm"
        >
          <span className="h-6 w-6 shrink-0 animate-pulse rounded-full bg-primary-400" />
          <span className="h-5 w-20 animate-pulse rounded-full bg-primary-400" />
          <span className="ml-auto h-5 w-10 animate-pulse rounded-full bg-primary-400" />
        </div>
      ))}
    </div>
  )
}
