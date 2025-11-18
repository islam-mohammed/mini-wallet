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
          <span v-else>{{ formattedBalance }} USD</span>
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
              Receiver username
            </label>
            <input
              v-model="form.receiverUsername"
              type="text"
              class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-sky-500"
              placeholder="Enter receiver username (e.g. alice)"
            />
            <p
              v-if="errors.receiver_username"
              class="text-xs text-red-400"
            >
              {{ errors.receiver_username }}
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
                USD
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
            <span v-if="formAmountNumber > 0">
              You will be charged
              <span class="font-semibold text-slate-200">
                {{ totalDebitPreview }} USD
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
                    Sent {{ tx.amount }} USD
                    <span class="text-slate-400">
                      to
                    </span>
                    <span class="text-slate-100">
                      {{ tx.receiver?.username ?? tx.receiver?.name ?? ('User #' + tx.receiver_id) }}
                    </span>
                  </span>
                  <span v-else>
                    Received {{ tx.amount }} USD
                    <span class="text-slate-400">
                      from
                    </span>
                    <span class="text-slate-100">
                      {{ tx.sender?.username ?? tx.sender?.name ?? ('User #' + tx.sender_id) }}
                    </span>
                  </span>
                </p>
                <p class="text-[11px] text-slate-400">
                  Commission: {{ tx.commission_fee }} USD
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
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { apiGet, apiPost, type ApiErrorResponse } from '@/lib/api'
import { useRouter } from 'vue-router'
import { isLoggedIn } from '../lib/auth'
import { echo } from '@/lib/echo'
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

const router = useRouter()

const currentUser = ref<WalletUser | null>(null)
const balance = ref<string>('0.0000')
const transactions = ref<WalletTransaction[]>([])

const isLoadingUser = ref(true)
const isLoadingTransactions = ref(true)
const isSubmitting = ref(false)

const errors = reactive<{
  receiver_username: string | null
  amount: string | null
}>({
  receiver_username: null,
  amount: null,
})

const generalError = ref<string | null>(null)

const form = reactive({
  receiverUsername: '',
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
  const num = Number(balance.value || '0')
  return Number.isFinite(num) ? num.toFixed(4) : '0.0000'
})

function formatDate(iso: string): string {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString(undefined, {
    dateStyle: 'short',
    timeStyle: 'short',
  })
}

/**
 * Map Laravel validation / error response into our local error state.
 */
function normalizeApiErrors(details?: ApiErrorResponse | null) {
  errors.receiver_username = null
  errors.amount = null
  generalError.value = null

  if (!details?.errors) {
    if (details?.message) {
      generalError.value = details.message
    }
    return
  }

  if (details.errors.receiver_username?.[0]) {
    errors.receiver_username = details.errors.receiver_username[0]
  }

  if (details.errors.amount?.[0]) {
    errors.amount = details.errors.amount[0]
  }

  if (details.message && !errors.amount && !errors.receiver_username) {
    generalError.value = details.message
  }
}

/**
 * Handle realtime broadcast: update list + balance.
 */
function handleRealtimeEvent(payload: TransactionCreatedPayload) {
  const tx = payload.transaction

  // Add transaction to list if it's not already there
  const exists = transactions.value.some((t) => t.id === tx.id)
  if (!exists) {
    transactions.value.unshift(tx)
  }

  if (!currentUser.value) return

  // Update balance for the current user depending on their role
  if (tx.sender_id === currentUser.value.id) {
    balance.value = payload.sender_balance
  } else if (tx.receiver_id === currentUser.value.id) {
    balance.value = payload.receiver_balance
  }
}

// keep track of which channel we joined so we can leave it
const currentChannelName = ref<string | null>(null)

/**
 * Subscribe to the user's private wallet channel.
 */
function subscribeToRealtime() {
  if (!currentUser.value) return

  const channelName = `wallet.user.${currentUser.value.id}`

  // If we were already on a different channel, leave it
  if (currentChannelName.value && currentChannelName.value !== channelName) {
    echo.leave(`private-${currentChannelName.value}`)
  }

  currentChannelName.value = channelName

  console.log('Subscribing to wallet channel', channelName)

  echo
    .private(channelName)
    // IMPORTANT: leading dot because backend uses broadcastAs('wallet.transaction.created')
    .listen('.wallet.transaction.created', (payload: TransactionCreatedPayload) => {
      console.log('Received wallet.transaction.created event:', payload)
      handleRealtimeEvent(payload)
    })
}

/**
 * Load current user + transactions and attach realtime listener.
 */
async function loadUserAndTransactions() {
  isLoadingUser.value = true
  isLoadingTransactions.value = true

  try {
    const [user, res] = await Promise.all([
      apiGet<UserResponse>('/user'),
      apiGet<TransactionsIndexResponse>('/transactions'),
    ])

    currentUser.value = user
    transactions.value = res.data
    balance.value = res.meta.balance

    subscribeToRealtime()
  } catch (err) {
    console.error(err)
    generalError.value = 'Failed to load wallet data. Please refresh.'
  } finally {
    isLoadingUser.value = false
    isLoadingTransactions.value = false
  }
}

/**
 * Submit transfer and handle backend validation errors.
 */
async function submit() {
  if (!currentUser.value) return

  isSubmitting.value = true
  normalizeApiErrors(undefined)

  try {
    const res = await apiPost<TransactionStoreResponse>('/transactions', {
      receiver_username: form.receiverUsername,
      amount: form.amount,
    })

    const tx = res.data

    const exists = transactions.value.some((t) => t.id === tx.id)
    if (!exists) {
      transactions.value.unshift(tx)
    }

    balance.value = res.meta.balance

    form.receiverUsername = ''
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
  if (!isLoggedIn()) {
    router.push('/login')
    return
  }

  void loadUserAndTransactions()
})

onUnmounted(() => {
  if (currentChannelName.value) {
    console.log('Leaving wallet channel', currentChannelName.value)
    echo.leave(`private-${currentChannelName.value}`)
  }
})
</script>

<route lang="yaml">
meta:
  requiresAuth: true
</route>
