import ConquestConditions from '@/components/conquests/Conditions'
import ConquestSelection from '@/components/conquests/ConquestSelection'
import ConquestCosts from '@/components/conquests/Costs'
import ConquestEffectsTable from '@/components/conquests/EffectsTable'
import ConquestHeader from '@/components/conquests/Header'
import ConquestStageHandler from '@/components/conquests/StageHandler'

export default function Conquests() {
  return (
    <div className="relative mx-auto flex h-screen w-full max-w-[90rem] flex-col items-center pt-[8rem] selection:bg-primary-800 md:pl-[3.75rem] md:pt-[4.3125rem] 2xl:pt-24">
      <ConquestSelection />

      <section className="flex w-full flex-col items-center px-4 py-4 text-white lg:px-8">
        <ConquestStageHandler />

        <ConquestHeader />

        <ConquestEffectsTable />

        <ConquestConditions />

        <ConquestCosts />
      </section>
    </div>
  )
}
