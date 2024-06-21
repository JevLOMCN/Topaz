import StatusSelectorSkeleton from '@/components/constitution/StatusSelector.skeleton'
import ConstitutionBackground from '@/icons/ConstitutionBackground'

export default function Loading() {
  return (
    <div className="relative mx-auto flex h-screen w-full max-w-[90rem] flex-col items-center gap-8 px-6 pt-20 selection:bg-primary-800">
      <div className="-pt-24 relative z-10 flex scale-75 items-center justify-center md:mb-28 md:mt-8 md:scale-125 lg:scale-100">
        <StatusSelectorSkeleton />
        <ConstitutionBackground className="h-[20rem] w-[20rem] lg:h-auto lg:w-auto" />
      </div>
    </div>
  )
}
