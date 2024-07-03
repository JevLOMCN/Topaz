import ConstitutionCostInformation from '@/components/constitution/CostInformation'
import ConstitutionStatsTable from '@/components/constitution/StatsTable'
import TierHandler from '@/components/constitution/TierHandler'
import ConstitutionWarning from '@/components/constitution/Warning'
import ConstitutionBackground from '@/icons/ConstitutionBackground'
import dynamic from 'next/dynamic'

const ConstitutionStatusSelector = dynamic(
  async () => await import('@/components/constitution/StatusSelector'),
  { ssr: false }
)

export default function Constitution() {
  return (
    <div className="relative mx-auto flex h-screen w-full max-w-[90rem] flex-col items-center gap-8 md:pl-[5.25rem] px-6 pt-32 md:pt-20 selection:bg-primary-800">
      <div className="-pt-24 relative z-10 flex scale-75 items-center justify-center md:mb-28 md:mt-8 md:scale-125 lg:scale-100">
        <ConstitutionStatusSelector />
        <TierHandler />
        <ConstitutionBackground className="h-[20rem] w-[20rem] lg:h-auto lg:w-auto [&>#cbDots]:origin-center [&>#cbDots]:animate-rotate [&>#cbLabyrinth]:origin-center [&>#cbLabyrinth]:animate-rotate [&>#cbMiddle]:origin-center [&>#cbMiddle]:animate-rotateInvert" />
      </div>

      <ConstitutionWarning />

      <div className="relative flex w-full flex-col items-center gap-12 pb-20 xl:flex-row xl:items-start">
        <ConstitutionStatsTable />
        <ConstitutionCostInformation />
      </div>
    </div>
  )
}
