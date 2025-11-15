<template>
  <section class="space-y-6">
    <header class="flex items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold">
          Wallet
        </h2>
        <p class="text-xs text-slate-400">
          View your balance, transaction history, and send money in real time.
        </p>
      </div>

      <div class="text-right">
        <p class="text-xs text-slate-400">
          Current balance
        </p>
        <p class="text-2xl font-bold tabular-nums">
          <span v-if="isLoadingUser">–</span>
          <span v-else>{{ formattedBalance }} EGP</span>
        </p>
      </div>
    </header>

    <!-- Transfer form -->
    <div class="grid gap-6 md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
      <div class="border border-slate-800 rounded-2xl p-4 bg-slate-900/40">
        <h3 class="text-sm font-semibold mb-3">
          Send money
        </h3>

        <form class="space-y-4" @submit.prevent="submit">
          <div class="space-y-1.5">
            <label class="text-xs text-slate-300">
              Receiver ID
            </label>
            <input
              v-model="form.receiverId"
              type="number"
              min="1"
              class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-sky-500"
              placeholder="Enter receiver user ID"
            />
            <p
              v-if="errors.receiver_id"
              class="text-xs text-red-400"
            >
              {{ errors.receiver_id }}
            </p>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs text-slate-300">
              Amount
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model="form.amount"
                type="number"
                min="0"
                step="0.0001"
                class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-sky-500"
                placeholder="0.0000"
              />
              <span class="text-xs text-slate-400">
                EGP
              </span>
            </div>
            <p
              v-if="errors.amount"
              class="text-xs text-red-400"
            >
              {{ errors.amount }}
            </p>
          </div>

          <div class="flex items-center justify-between text-xs text-slate-400">
            <span>Commission: 1.5% paid by sender</span>
            <span v-if="form.amountNumber > 0">
              You will be charged
              <span class="font-semibold text-slate-200">
                {{ totalDebitPreview }} EGP
              </span>
            </span>
          </div>

          <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-xs font-semibold tracking-wide disabled:opacity-50 disabled:cursor-not-allowed hover:bg-sky-500 transition"
            :disabled="isSubmitting || isLoadingUser"
          >
            <span v-if="isSubmitting">
              Sending...
            </span>
            <span v-else>
              Send money
            </span>
          </button>

          <p
            v-if="generalError"
            class="text-xs text-red-400 mt-1"
          >
            {{ generalError }}
          </p>
        </form>
      </div>

      <!-- Transaction list -->
      <div class="border border-slate-800 rounded-2xl p-4 bg-slate-900/40 min-h-[260px]">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold">
            Transactions
          </h3>
          <span class="text-[11px] text-slate-400">
            Latest activity
          </span>
        </div>

        <div v-if="isLoadingTransactions" class="text-xs text-slate-400">
          Loading transactions...
        </div>

        <div v-else-if="transactions.length === 0" class="text-xs text-slate-400">
          No transactions yet. Send money to see activity here.
        </div>

        <ul v-else class="space-y-2 max-h-[360px] overflow-y-auto pr-1">
          <li
            v-for="tx in transactions"
            :key="tx.id"
            class="flex items-center justify-between rounded-xl border border-slate-800/70 bg-slate-900/60 px-3 py-2 text-xs"
          >
            <div class="flex items-center gap-3">
              <div
                class="h-8 w-8 rounded-full flex items-center justify-center text-[11px] font-bold"
                :class="tx.direction === 'outgoing'
                  ? 'bg-rose-500/20 text-rose-300'
                  : 'bg-emerald-500/20 text-emerald-300'"
              >
                <span v-if="tx.direction === 'outgoing'">−</span>
                <span v-else>+</span>
              </div>

              <div>
                <p class="font-medium">
                  <span v-if="tx.direction === 'outgoing'">
                    Sent {{ tx.amount }} EGP
                    <span class="text-slate-400">
                      to
                    </span>
                    <span class="text-slate-100">
                      {{ tx.receiver?.name ?? ('User #' + tx.receiver_id) }}
                    </span>
                  </span>
                  <span v-else>
                    Received {{ tx.amount }} EGP
                    <span class="text-slate-400">
                      from
                    </span>
                    <span class="text-slate-100">
                      {{ tx.sender?.name ?? ('User #' + tx.sender_id) }}
                    </span>
                  </span>
                </p>
                <p class="text-[11px] text-slate-400">
                  Commission: {{ tx.commission_fee }} EGP
                </p>
              </div>
            </div>

            <div class="text-right text-[11px] text-slate-500">
              <span v-if="tx.created_at">
                {{ formatDate(tx.created_at) }}
              </span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { apiGet, apiPost, type ApiErrorResponse } from '@/lib/api'
import { useWalletRealtime } from '@/composables/useWalletRealtime'
import type {
  WalletTransaction,
  WalletUser,
  TransactionCreatedPayload,
} from '@/lib/wallet-realtime'

interface UserResponse extends WalletUser {}

interface TransactionsIndexResponse {
  data: WalletTransaction[]
  meta: {
    balance: string
    next_cursor?: string | null
  }
}

interface TransactionStoreResponse {
  data: WalletTransaction
  meta: {
    balance: string
  }
}

const currentUser = ref<WalletUser | null>(null)
const balance = ref<string>('0.0000')
const transactions = ref<WalletTransaction[]>([])

const isLoadingUser = ref(true)
const isLoadingTransactions = ref(true)
const isSubmitting = ref(false)

const errors = reactive<Record<string, string | null>>({
  receiver_id: null,
  amount: null,
})

const generalError = ref<string | null>(null)

const form = reactive({
  receiverId: '',
  amount: '',
})

const formAmountNumber = computed(() =>
  form.amount ? Number(form.amount) : 0,
)

const totalDebitPreview = computed(() => {
  if (formAmountNumber.value <= 0) return '0.0000'
  const amount = formAmountNumber.value
  const commission = amount * 0.015
  const total = amount + commission
  return total.toFixed(4)
})

const formattedBalance = computed(() => {
  return Number(balance.value).toFixed(4)
})

function formatDate(iso: string): string {
  const d = new Date(iso)
  return d.toLocaleString(undefined, {
    dateStyle: 'short',
    timeStyle: 'short',
  })
}

function normalizeApiErrors(details?: ApiErrorResponse | null) {
  errors.receiver_id = null
  errors.amount = null
  generalError.value = null

  if (!details?.errors) {
    if (details?.message) {
      generalError.value = details.message
    }
    return
  }

  if (details.errors.receiver_id?.[0]) {
    errors.receiver_id = details.errors.receiver_id[0]
  }

  if (details.errors.amount?.[0]) {
    errors.amount = details.errors.amount[0]
  }

  if (details.message && !errors.amount && !errors.receiver_id) {
    generalError.value = details.message
  }
}

async function loadUserAndTransactions() {
  try {
    isLoadingUser.value = true

    const user = await apiGet<UserResponse>('/user')
    currentUser.value = user
    isLoadingUser.value = false

    isLoadingTransactions.value = true
    const res = await apiGet<TransactionsIndexResponse>('/transactions')

    transactions.value = res.data
    balance.value = res.meta.balance
  } catch (err) {
    generalError.value = 'Failed to load wallet data. Please refresh.'
    isLoadingUser.value = false
    isLoadingTransactions.value = false
    return
  } finally {
    isLoadingTransactions.value = false
  }

  if (currentUser.value) {
    useWalletRealtime(currentUser.value.id, handleRealtimeEvent)
  }
}

function handleRealtimeEvent(payload: TransactionCreatedPayload) {
  const tx = payload.transaction

  const exists = transactions.value.some((t) => t.id === tx.id)
  if (!exists) {
    transactions.value.unshift(tx)
  }

  if (!currentUser.value) return

  if (tx.sender_id === currentUser.value.id) {
    balance.value = payload.sender_balance
  } else if (tx.receiver_id === currentUser.value.id) {
    balance.value = payload.receiver_balance
  }
}

async function submit() {
  if (!currentUser.value) return

  isSubmitting.value = true
  normalizeApiErrors(undefined)

  try {
    const res = await apiPost<TransactionStoreResponse>('/transactions', {
      receiver_id: Number(form.receiverId),
      amount: form.amount,
    })

    // Update list optimistically using server truth
    const tx = res.data
    const exists = transactions.value.some((t) => t.id === tx.id)
    if (!exists) {
      transactions.value.unshift(tx)
    }

    balance.value = res.meta.balance

    form.receiverId = ''
    form.amount = ''
  } catch (err) {
    const anyErr = err as any
    const details: ApiErrorResponse | undefined = anyErr.details
    normalizeApiErrors(details ?? null)
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  void loadUserAndTransactions()
})

const formAmountNumber = computed(() =>
  form.amount ? Number(form.amount) : 0,
)

</script>
