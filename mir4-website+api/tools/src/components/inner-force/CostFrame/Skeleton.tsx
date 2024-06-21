export default function CostFrameSkeleton() {
  return (
    <div className="flex w-56 items-center gap-3 rounded-full bg-primary-600 px-3 py-2 pr-6 text-xl font-bold text-white">
      <span className="h-8 w-8 animate-pulse rounded-full bg-primary-400" />
      <span className="h-6 w-20 animate-pulse rounded-full bg-primary-400" />
    </div>
  )
}
