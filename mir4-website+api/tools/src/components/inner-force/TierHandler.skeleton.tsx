export default function TierHandlerSkeleton() {
  return (
    <div className="flex h-12 w-36 items-center text-base font-bold text-white">
      <button
        aria-label={'Decrement constitution tier'}
        className="h-full rounded-l-full bg-primary-500 px-3 py-2 transition-colors hover:bg-primary-450"
        disabled
      >
        -
      </button>
      <div className="flex h-full items-center bg-primary-500 px-1 py-2 text-center">
        <span className="h-6 w-[3.25rem] animate-pulse rounded-full bg-primary-400" />
      </div>
      <button
        aria-label={'Increment constitution tier'}
        className="h-full rounded-r-full bg-primary-500 px-3 py-2 transition-colors hover:bg-primary-450"
        disabled
      >
        +
      </button>
    </div>
  )
}
