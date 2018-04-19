A Modular Low-Complexity ECG Delineation Algorithm for Real-Time Embedded Systems
===
IEEE JOURNAL OF BIOMEDICAL AND HEALTH INFORMATICS, VOL. 22, NO. 2, MARCH 2018
# 摘要
![](https://www.researchgate.net/profile/Hiren_Kumar_Thakkar/publication/322752368/figure/fig7/AS:587730631290886@1517137310294/Example-of-ECG-feature-points-onset-points-and-offset-points.png)
這個做法對於不同ECG波型的區分(QRS、P和T波、onsets和結束波型)展現了一個新的模塊化且低複雜度的演算法。包含減少每秒的運算數量和使用很少的內存空間，這個演算法的用法是在資源受限的嵌入式系統實現即時判斷。模組化設計允許演算法可以在執行時自動調整劃定品質的模式範圍和取樣率，從沒有心電異常時使用超低電量模式來用低頻率取樣，到以高頻率取樣的高精度取樣劃分模式，偵測所有ECG心率不準時的基準點。劃分演算法已經被用QT資料庫調整過了，提供非常高的靈敏度和正確預測能力，從MIT資料庫驗證。在異常判斷中，劃分的所有基準點都是從心電圖委員會高精確度模式通用標準中的公差取得，除了P波的部分，藉由一段時間的樣本，在驗證基於公差上的演算法。

###### tags: `Electrocardiogram` `delineation` `real-time`
###### tags: energy-constrained systems` `wearable medical devices

# I.介紹
ECG信號代表心臟的電子活動狀態，這是觀察心臟狀態和評估其條件非常重要的工具。大多的心電信號自動分析技術依靠特徵點，因此，準確地偵測不同的心電圖波型峰值和邊界是對完成可靠的心臟自動診斷議題是重要的。在很多狀況中(例如動態監控、可提取不同心電圖波形的即時自動化系統)是有用的。

第一個應用的技術是為了劃分ECG信號的QRS複合波基於ECG的振幅和它的一階微分。雖然這些實例是相當重要有效，並具有高靈敏度和特異性的，但他們僅限於用來偵測QRS複合波，然而，為了進行準確的ECG分析，P波和T波的邊界和峰值也是需要的。如今，基準點都已經開發有大量更先進的方法來求得。這些方法包含使用小波(使用最廣泛的技術，即使是用於檢測心電圖波形形態的心肌疤痕)、希爾伯特變換(Hilbert transform)、法則轉換(fasor transform)、動態時間扭曲(dynamic time warping)、人工神經網路(artificial neural networks)、隱馬爾可夫模型(hidden Markov models)、時域形態(time-domain morphology)、梯度演算法或型態轉換(gradient based algorithms or morphological transforms)。

這些新方法的複雜性涵蓋很高的計算成本。若是劃分在離線或沒有任何時間限制的情況下執行，他們可以非常近似於醫學註釋。然而，越來越多地使用微型嵌入式可穿戴設備進行即時健康監測和診斷，這促使人們需要更高效的演算法，以適應這些設備的低處理能力和能量消耗，同時實現醫療標準所要求的高精度。部分的裝置已經上市，例如Corventis NUVANT Mobile、Cardiac Telemetry System、Vital Connect HealthPatch MD或Preventice BodyGuardian Remote Monitoring System。然而他們他們僅限於ECG的串連或非常簡單的處理，來提取心跳等關鍵參數。

目前穿戴式設備市場穩定成長，開發能夠全天候(24/7)監測心率（HR）的新設備，使用GPS運行或監測睡眠模式和品質。健身追踪器或智能手錶可用於記錄和/或分析實時信號，而無需安裝在設備上的傳感器或胸帶。在心率的範例中，這些設備如同腕帶式設備模型或是Garmin v´ıvo系列，可以持續5至8天連續以光學方式測量心率。另一方面，如Polar M400用可穿戴設備胸帶和智慧手錶測量心率。

每年市場都有很多新產品上市，像是Cronovo ECG monitor watch，能監測血壓水準、心跳異變率、疲勞程度、自動SOS警報或睡意警報，或像是QardioCore有三導程無線ECG監控、臨床驗證、使用取樣率200Hz、16bit解析度，可連續使用兩天。對於本論文建議的即時心電圖劃分演算法將會幫助下一代可穿戴監控系統的發展，可以憑藉劃分結果在電路板上進行心電圖心律失常檢測。

為了實現這些可穿戴式嵌入式系統適合的演算法，提出了一種基於小波的即時劃分線，並強調嵌入式信號濾波的重要性，以補償在動態監測期間出現的不同信號來源或雜訊和人為干擾。在本論文中我們更進一步提出了與心跳分類演算法類似的結構，其中提供了一種簡單且高效的分類演算法，可隨時執行，以確定心電圖的哪些部分是具有關鍵性的，並且僅在這些情況下使用詳細的（並且提供更高的運算量）診斷演算法;以及在其實時硬體實現中使用濾波器和閾值來檢測ECG基準點以預測室性心律失常。因此，本文提出了一種新的劃分演算法，該演算法不僅比先進技術有更低的運算複雜度且更省效能，而且更加靈活和可模塊化，能夠持續實現能量消耗與劃分精度之間的最佳平衡，能在有限的能量和效能需求中，進行精確的心律失常檢測。

本文提出的簡單而精確的劃分演算法是對ECG信號的二階導數做截止頻率為14Hz的低通FIR濾波，也考慮雜訊濾除和消除基線飄移，雜訊濾除部分採用頻率為40Hz的低通FIR濾波器，消除基線漂移的部分則應用不同的形態濾波器。與以前的方法相比，所提出的算法的優點如下：

1) 在本論文中介紹的劃定算法中，取樣速率會在執行時進行調整，以降低CPU處理和ADC的運算耗能。小波技術需要重新設計濾波組合或重新取樣輸入信號(包含負樣本)，而本論文所提出的演算法只需要調整FIR濾波器的係數維持其截止頻率即可（參數可以被離線運算和被儲存在某些集合頻率中的快取記憶體中）。
2) 本論文的技術能夠以幾乎沒有延遲的方式檢測心跳，所以能允許ADC關閉，直到預測下次的心跳發生的時候開啟，達到系統節能。相反，小波變換使用滑動窗口來檢測心跳，因此需要其中的所有取樣點。
3) 如果不需要完整劃分，例如心率是正常的，受試者沒有病症，模塊化的算法法允許一些部分被禁用，因此大大減少了運算負擔。如果給定的心臟狀況（例如檢測到心動過速或心動過緩），系統也會自動啟用所有模塊。在小波方法中，大部分CPU容量用於計算比例，例如R峰值偵測就用了大部分的容量，未檢測其他基準點不會顯著減輕CPU負擔。

本論文的其餘部分如下。第二部分介紹劃分演算法建議的模組化設計，包含一個演算法其根據心率變化自動改變取樣品質的研究案例。第三部分描述了MSPSim演算法的實現，並提供了它的性能評估，以及與其他最先進技術的比較研究。第四部分為這項論文的結論。

# II.心電圖劃分演算法
EGG信號呈現不同的波形，每個波形都與心動週期的生理階段有關。如Fig.1 所示，心跳可以分為三個主要部分：P波、QRS複合波和T波。 P波表示開始並且代表心房去極化。 QRS複合波的是一組三個連續的波並相應
到心室去極化。最後，T波表示心室復極。
![](https://i.imgur.com/3tcROTC.png)
在這裡採用一種新的模塊化演算法來檢測不同的心電波（P，QRS和T）及描述其基準點（峰值，起始和結束）被。檢測ECG波形的偵測演算法總結如下（Fig.2）：
![](https://i.imgur.com/zEeb6RS.png)
1) 劃分演算法從QRS峰開始。
2) 檢測到一個QRS峰值後，其邊界（開始和結束）就能在複合波之前和之後被找到。
3) P和T也可以在QRS前後被找到，然後同樣找P和T的起始和終止時間。

當取得原始ECG信號時，高頻雜訊、人為造成的肌肉運動和基線漂移都可能存在。這些干擾需要在使用劃分演算法之前去除，以確保檢測基準點的準確性。Fig.3 為ECG信號進行劃定演算法時的流程圖。 Peaks模塊（左）是用於檢測QRS波峰以及可選的P和T峰。 On-end模塊（右側）可用於描繪如果需要，ECG波的起始和結束。 On-end模塊細分為三個完全獨立的模塊模塊：QRS端到端模塊，P端模塊和可根據需要啟用或禁用T端模塊。
![](https://i.imgur.com/zd0J7hU.png)
## A. Peaks Module
主要為檢測QRS波的波峰，甚至可以檢測P和T波的波峰。
將截止頻率為14Hz的低通FIR濾波器應用於$ECG_{raw}$信號。使用這種強大的濾波器，可以消除任何類型的高頻信號，同時保留P、QRS和T波峰位置，如Fig.4中的$ECG_{LP14}$信號。因此，$ECG_{LP14}$用於查找P，T波和QRS波群，由於他有使用閥值的概念，因此無法檢測到更小的低能波，如Q、S.
![](https://i.imgur.com/bFfQpAx.png)
雖然IIR濾波器比起FIR濾波器可以使用更少的接頭、記憶體和運算子來實現信號濾波，但它更容易受到數值穩定性問題的影響。因此，選擇FIR濾波器是因為它們更容易實時實現（較低的複雜度），並且它們的穩定性也被證實了，雖然他需要更多的樣本來做信號處理而且會有比較高的信號延遲發生(N-order FIR 濾波器產生N/2 samples的延遲時間)。
QRS的劃分採用$ECG_{LP14}$信號的最高峰。由於QRS波群的形狀取決於所選導聯的方向，因此最顯著的峰是某些導聯（如V6）的R波或其他導聯（如V1）的S波，其中隨後出現一個小的R峰由大負偏轉（S波）。在本文中，我們將前QRS複合波定義為正值，後者定義為負值。
為了偵測QRS波群的峰值，運算一階和二階微分。如Fig. 5(a)，對於正QRS波峰，二階導數總是負值，其值在峰值附近大於（在絕對值上）比其餘節拍中的值，並且一階導數穿過零線。在負QRS波峰的情況下，二階導數總是正的。正常心電圖描繪在Fig. 6中，包含正負QRS波群。
![](https://i.imgur.com/DvDNTit.png)
![](https://i.imgur.com/RIoNvmQ.png)
QRS偵測峰值的第一步是選擇一段濾波後的ECG信號（來自$ECG_{LP14}$），其大小至少包含一次心跳;將這段信號存儲在一個2秒的循環緩衝區（也稱為window）中，並以新的ECG取樣做更新。緩衝長度為2秒的長度可允許心率低至每分鐘30次。每當2秒window（或循環緩衝區）完成時，找到二階導數的最大值$(max(ECG''_{LP14}))$和最小值$(min(ECG''_{LP14}))$來計算將在稍後使用的值。最後，最後5個值的移動平均值（AvgQRS）將在稍後用於計算閾值以檢測QRS波峰;每當新窗口完成時，此閾值就會更新。

當滿足以下條件時（在每一個新ECG樣本獲得時評估），在環形緩衝區內偵測到QRS峰值：
1) $ECG_{LP14}$的一階導數的符號必須改變（標記為$ECG_{LP14_{,sc}}$如Fig.3）。這表示濾波的ECG信號中存在局部最大值或最小值。
2) $ECG_{LP14}$的二階導數的幅度（絕對值）必須大於閾值 $(QRS_{th} = QRS_{factor}·Avg_{QRS})$，其中$QRS_{factor} = 0.33$稍後會有證明，而$Avg_{QRS}$之前被定義為最後五個最小或最大window值的移動平均值，因此，$R_{th}$每2秒更新一次（每次在新的2秒window行程後更新$Avg_{QRS}$）以動態地使算法適應新的一段ECG。應該注意的是$QRS_{factor}$應該在0和1之間。
3) 連續QRS波峰兩個之間的時差必須大於250ms。這個時間間隔允許檢測高達每分鐘240次的心率。

針對每個新ECG樣本的輸入來評估檢測QRS峰值的條件。應該注意的是，FIR濾波器，形態濾波器和導數運算與串流資料在取樣和運算後都有已知的延遲。一旦偵測到QRS峰，根據需要劃定P和T峰，在2個連續的QRS峰之間搜索它們（前一次的T波，與這次的P波）。

P波是波型中的第一波。在正常患者在120ms附近，例如有心房纖顫史的患者，長達170ms。另外，PR間隔（Fig.1）在120 ms和200 ms之間。由於這個原因，劃分演算法在QRS波峰之前的100ms和200ms之間的時間窗口中尋找可能的P峰值，永遠不會超過當前QRS峰值和前一波形之間一半的時間，以避免混淆波以前的心跳為當前的P波。

一個P波峰值的候選點，必為$ECG_{LP14}$的二階導數中的搜索window中的最小值或最大值（如Fig.5（b）），並超過某個閾值$(P_{th} = P_{factor}·Avg_{QRS})$，與QRS峰值偵測的程序類似。對於正常或正P波，使用二階導數絕對值的最小值會比最大值來的好，而對於異常或負P波，絕對值的最大值比最小值佳。在這種情況下，使用閾值避免錯誤偵測，根據經驗選擇為已$ECG_{LP14}$的二階導數的1％做為QRS峰值位置$(P_{factor} = 0.01)$中，並且在下一節中將被證明是合理的。如果這個條件沒有得到滿足，P波就不會被標出來。

心跳的最後一個特徵波是T波。對於正常的T波，QT間隔必須短於425ms，T波持續時間從125ms到200 ms。在這些情況下，劃分演算法在QRS複合波之後200ms和400ms之間的時間間隔內搜索T波的峰值，並且絕不超過當前QRS峰值和下一波峰值之間一半的時間。

檢測T波的過程與之前解釋的P波檢測完全相同。此外，使用的閾值是$T_{th} = T_{factor}·Avg{QRS}$，$T_{factor} = 0.01$，將在下一節討論。


## B. On-End Module
與Peaks模塊同步執行的On-end模塊可用於檢測以前檢測到的ECG波的起始點和結束點。這是一個不太具有侵略性的低通FIR濾波器應用於$ECG_{raw}$，截止頻率為40Hz（信號$ECG_{LP40}$）。該FIR濾波器可清除保存ECG波形的高頻雜訊，因此$ECG_{LP40}$適用於檢測ECG波的起點和終點。
### 1) QRS-on-End Module
為了能夠檢測心電圖起點和終點，必須從$ECG_{LP40}$中去除心電圖基線漂移。為此，將$ECG_{LP40}$送往高通濾波器到產生ECGbaseline信號（ECGbase）的高通濾波器。使用FIR濾波器或形態濾波器的方法可以在大多數技術中的其他研究中找到，以消除ECG基線漂移。在本文中，使用了描述的形態濾波。
In order to do so, ECGLP 40 is fed to a highpass filter producing the ECGbaseline signal (ECGbase ). Methods using FIR filters or morphological filters can be found in other studies among most techniques to remove the ECG baseline wander. In this paper, morphological filtering described in is used.

This filter consist of two basic morphological operations: opening, f ◦ Bop = f  Bop ⊕ Bop , (that removes peaks from the signal) and closing, f • Bcl = f ⊕ Bcl  Bcl , (removes valleys), where f is the signal to be filtered, B denotes a horizontal line segment of zero amplitude, and  and ⊕ correspond with the erosion and dilation operands respectively. The length of Bop is chosen as 0.2 s because this value is greater than the width of the characteristic waves of the ECG signal (P, Q, R, S and T wave, individually). The length of Bcl should be longer than the length of Bop , typically it is selected to be 1.5 times the length of Bop , therefore 0.3 s is chosen. Each operation introduces a delay that is equal to half of the window length used, thus the morphological filter has a total delay of 0.5 s.

This type of filter presents a lower computational cost than other techniques since it only requires to find the largest or smallest value in an array of B values. An example of ECGbase (Baseline wander) along with opening and closing operations is shown in Fig. 7.

In the vicinity of a QRS peak, the ECG baseline wander (ECGbase ) is used to detect its onset and end. Supposing a positive QRS complex, the QRS peak is an R peak. Theoretically, the crossing of ECGLP 40 with ECGbase determines two characteristic points: the previous crossing would be the onset of the R wave and the later crossing would be the end of the R wave.

However, the ECGLP 40 might not cross ECGbase near the R wave, especially if the Q or S waves are absent (Fig. 8). To prevent that the algorithm fails to detect the onset and end properly, these points are selected as5%of theRwave amplitude instead of the true baseline crossings, where the amplitude is defined as the difference between ECGLP 40 and ECGbase in the R peak position.

Once the onset of the R wave is found, a Q wave may be detected before the onset of the R wave. If the Q wave is present, it is smaller than the R wave and its duration does not exceed 20/25 ms in a normal Q wave [29]. To consider the existence of a Q wave, three rules have to be met:
1) The wave is found before an R wave and is negative.
2) Its duration is less than 100 ms.
3) Its amplitude is greater than 5% of the R wave amplitude. This value is chosen empirically and detections below this value are considered as variations due to noise.


Finally, if aQwave is detected, the first crossing ofECGLP 40 withECGbase determines the onset of the Q wave. That point is also annotated as the onset of the QRS complex. Nevertheless, if the algorithm does not detect the existence of a Q wave, the onset of the R wave is annotated as the onset of the QRS complex. In order to improve the final positioning of the QRS
complex onset, the algorithm searches for a local maximum of ECGLP 40 in the previous 20 ms. If such maximum exists, the onset of the QRS complex is updated to that new position.

If an R wave is detected, a S wave may follow it. The requirements of considering S waves and the treatment are the same as for Q waves. The only difference is that the search occurs (in time) after R peaks instead of before. If a S wave is annotated, the end of the S wave is also the end of the QRS complex. Otherwise, the end of the R wave is the end of the QRS complex.

In case of negative QRS complexes, the detection is analogous, but the QRS peak corresponds to an S wave and the algorithm looks for positive deflections (R waves) before and after that S wave.
![](https://i.imgur.com/tPutbjp.png)
![](https://i.imgur.com/pUnikNO.png)
### 2) P-on-End Module
To detect the onset and end of the P wave, the crossings of ECGLP 40 with the baseline have to be found (to the left and right of the peak, respectively). For this purpose, the Pbase signal (see Fig. 3) is used. This signal is obtained by applying the morphological operation defined previously as opening (II-B1) to ECGLP 40, which removes the peaks of the signal, in the same way as in ECGbase . However, the length of the segmentBop in this case differs. Since a normal P wave has a maximum length of 120 ms, the length of Bop must be in agreement with this physiological property. Choosing Bop = 120 ms, opening removes peaks whose width is less than this time and makes a good approximation to the baseline of a P wave.

As well as for a QRS complex, the onset and end of a P wave are, theoretically, the crossings of ECGLP 40 with Pbase . However, it may be possible that these crossings do not take place near the peak of the P wave. For this reason, the onset and end are defined as the first local minima (to the left and right of the peak, respectively) found in ECGLP 40 for which the difference between ECGLP 40 and Pbase is between 50% and 2.5% of the amplitude of the P wave, defined as the difference between ECGLP 40 and Pbase in the P peak position.
### 3) T-End Module
As in the detection of the onset and end of the P wave, the baseline of the T wave (Tbase ) is needed (see Fig. 3). This signal is computed using the opening operation (IIB1) with a Bop = 200 ms, as a normal T wave must not exceed 200 ms. The crossing of ECGLP 40 with Tbase to the right of the peak of the T wave should determine the end. However, as in P waves, this point has been redefined as the first local minima found in ECGLP 40 for which the difference between ECGLP 40 and Tbase is between 50% and 2.5% of the T wave amplitude, in order to obtain better results. The algorithm is able to detect both types of T waves, normal and abnormal, although biphasic T waves may not be accurately detected.
A complete result of the ECG delineation described is shown in Fig. 9. This excerpt corresponds to the sel16539 record obtained from the QT database.
![](https://i.imgur.com/4nedVfe.png)
## C. Validation of the Algorithm
![](https://i.imgur.com/DgY5b4x.png)
## D. Adjusting the Algorithm Parameters
![](https://i.imgur.com/rBVQFGs.png)
## E. Computational Cost Versus Accuracy Tradeoff
![](https://i.imgur.com/ocgAavQ.png)
![](https://i.imgur.com/yaZN8MK.png)
# III.實驗結果
## A. Comparison With Other Methods
![](https://i.imgur.com/EsI90nI.png)
## B. MIT-BIH Arrhythmia Database
![](https://i.imgur.com/vbdxk3V.png)
![](https://i.imgur.com/BEUKmUw.png) 
### 1) Noise
## C. Estimated Number of Arithmetic Operations
### 1) FIR Filtering
### 2) Morphological Filtering
## D. Real-Time Simulation
![](https://i.imgur.com/C0kvPwZ.png)
# IV. 總結
In this paper, a new simple, modular and effective algorithm has been developed to delineate and locate the peaks and boundaries of the different ECG waves, such as the P wave, the QRS complex and the T wave, featuring low computational cost and mathematical complexity while achieving similar or better accuracy than other more advanced and computationally intensive state-of-the-art techniques.The algorithm is conceived as a modular design, in which the Peaks module delineates the ECG peaks, computing the first and second derivatives of an aggressive low-pass filtered signal, and the On-end module detects the onsets and ends of the peaks, using a less aggressive low-pass filter and morphological filters.
To validate the performance of the proposed algorithm, the QT and MIT-BIH databases have been delineated and the results have been compared with annotations made by cardiologists for these databases. Results show sensitivity higher than 98% when detecting the ECG wave peaks and 96% for onsets and ends, and better positive predictive value in contrast to more complex techniques as, for example,wavelets or fasor ransform.
Moreover, the errors in the delineation of all the fiducial points are below the tolerances given by the Common Standards for Electrocardiography (CSE) committee, except for the P wave onset, for which the algorithm is above the agreed tolerances by only a fraction of the sample duration.
The presented algorithm has the advantage of being highly adaptive and modular. The system is able to automatically modify the sampling rate or to disable some modules of the algorithm such as for P or T wave detectors, or the detection of onsets and ends of the ECG waves, depending whether a complete delineation is needed, because an abnormal cardiac event is detected, or performing a reduced delineation the rest of the time for energy saving. This flexibility allows the algorithm to be able to operate in different modes dealing with the tradeoff between delineation accuracy and energy consumption. The proposed technique has been implemented in a simulated commercial WBSN platform to analyze its computational burden and memory footprint showing that it can perform a complete high-accuracy real-time ECG delineation with a computational burden of 8.5% (8.6 weeks of battery lifetime just considering data acquisition and processing), similar to thewavelet approach in, and also a real-time detection of only the QRS peaks, reducing the computational load to only 0.2% (31 weeks of battery lifetime) and achieving 70% power saving with respect to a complete delineation.
The system can also be configured to automatically switch between modes, denoted as Adaptivemode, in order to perform a full high-accuracy delineation during arrhythmias and a limited ultra-low power detection when no arrhythmia is detected. This mode has a combined computational load of 1.3% (21.7 weeks of battery lifetime).
Its low complexity, modularity in the delineation, low-power consumption, as well as its easy and quick adaptability to the ECG sampling rate, offer a great flexibility and qualify the proposed algorithm as an useful tool for embedded medical devices with limited resources for real-time applications.
# 參考文獻
[1] J. Pan and W. J. Tompkins, “A real-time QRS detection algorithm,” IEEE
Trans. Biomed. Eng., vol. BME-32, no. 3, pp. 230–236, Mar. 1985.
[2] V. Bono et al., “Development of an automated updated selvester QRS scoring system using SWT-based QRS fractionation detection and classification,” IEEE J. Biomed. Health Informat., vol. 18, no. 1, pp. 193–204, Jan. 2014.
[3] C. Li, C. Zheng, and C. Tai, “Detection of ECG characteristic points using wavelet transforms,” IEEE Trans. Biomed. Eng., vol. 42, no. 1, pp. 21–28, Jan. 1995.
[4] J. Sahambi, S. Tandon, and R. Bhatt, “Using wavelet transforms for ECG characterization. An on-line digital signal processing system,” IEEE Eng.
Med. Biol. Mag., vol. 16, no. 1, pp. 77–83, Jan./Feb. 1997.
[5] J. P. Mart´ınez, R. Almeida, S. Olmos, A. P. Rocha, and P. Laguna, “A wavelet-based ECG delineator: Evaluation on standard databases,” IEEE Trans. Biomed. Eng., vol. 51, no. 4, pp. 570–581, Apr. 2004.
[6] D. Benitez, P. Gaydecki, A. Zaidi, and A. Fitzpatrick, “The use of the hilbert transform in ECG signal analysis,” Comput. Biol. Med., vol. 31, no. 5, pp. 399–406, 2001.
[7] A. Martinez, R. Alcaraz, and J. J. Rieta, “Automatic electrocardiogram delineator based on the phasor transform of single lead recordings,” in
Proc. Comput. Cardiol., 2010, pp. 987–990.
[8] H. Vullings, M. Verhaegen, and H. Verbruggen, “Automated ECG segmentation with dynamic time warping,” in Proc. 20th Annu. Int. Conf. IEEE Eng. Med. Biol. Soc., Oct. 1998, vol. 1, pp. 163–166.
[9] Z. Dokur, T. Olmez, M. Korurek, and E. Yazgan, “Detection of ECG waveforms by using artificial neural networks,” in Proc. 18th Annu. Int. Conf. IEEE Eng. Med. Biol. Soc., Oct. 1996, vol. 3, pp. 929–930.
[10] S. Graja and J.-M. Boucher, “Hidden markov tree model applied to ecg delineation,” IEEE Trans. Instrum. Meas., vol. 54, no. 6, pp. 2163–2168,
Dec. 2005.
[11] E. B. Mazomenos, T.Chen, A. charyya, A.Bhattacharya, J.Rosengarten, and K. Maharatna, “A time-domain morphology and gradient based algorithm for ECG feature extraction,” in Proc. IEEE Int. Conf. Ind. Technol.,
Mar. 2012, pp. 117–122.
[12] Y. Sun, K. Luk Chan, and S.Muthu Krishnan, “ Characteristic wave detection in ECG signal using morphological transform,” BMC Cardiovascular Disorders, vol. 5, no. 1, 2005, Art. no. 28.
[13] Corventis NUVANT Mobile Cardiac Telemetry System, 2017. [Online].Available: http://www.corventis.com/
[14] Vital Connect HealthPatch MD, 2017. [Online]. Available: http://www.vitalconnect.com/
[15] Preventice BodyGuardian Remote Monitoring System, 2017. [Online].Available: http://www.preventice.com/
[16] Fitbit, 2017. [Online]. Available: https://www.fitbit.com/
[17] Garmin, V´ıvo-Series, 2017. [Online]. Available: https://explore.garmin.com/en-US/vivo-fitness/
[18] Polar, M400, 2017. [Online]. Available: https://www.polar.com/en/products/sport/M400
[19] Cronovo, 2017. [Online]. Available: http://www.cronovo.com/
[20] QardioCore, 2017. [Online]. Available: https://www.getqardio.com/qardiocore-wearable-ecg-ekg-monitor-iphone/
[21] F. Rinc´on, J. Recas, N. Khaled, and D. Atienza, “Development and evaluation ofmultileadwavelet-based ECGdelineation algorithms for embedded wireless sensor nodes,” IEEE Trans. Inf. Technol. Biomed., vol. 15, no. 6, pp. 854–863, Nov. 2011.
[22] R. Braojos Lopez, G. Ansaloni, and D. Atienza Alonso, “A methodology for embedded classification of heartbeats using random projections,” in Proc. Des. Autom. Test Europe Conf., 2013, pp. 899–904.
[23] N. Bayasi, T. Tekeste, H. Saleh, B. Mohammad, A. Khandoker, and M. Ismail, “Low-power ECG-based processor for predicting ventricular arrhythmia,” IEEE Trans. Very Large Scale Integr. Syst., vol. 24, no. 5, pp. 1962–1974, May 2016.
[24] S. A. Guidera and J. S. Steinberg, “The signal-averaged P wave duration: A rapid and noninvasivemarker of risk of atrial fibrillation,” J. Amer. Coll. Cardiol., vol. 21, no. 7, pp. 1645–1651, 1993.
[25] C. Saritha, V. Sukanya, and Y. N. Murthy, “ECG signal analysis using wavelet transforms,” Bulgarian J. Phys., vol. 35, no. 1, pp. 68–77, 2008.
[26] R. B. Northrop, Noninvasive Instrumentation and Measurement inMedical Diagnosis. Boca Raton, FL, USA: CRC Press, 2001.
[27] J. Van Alste and T. Schilder, “Removal of base-line wander and powerline interference from the ECG by an efficient FIR filter with a reduced number of taps,” IEEE Trans. Biomed. Eng., vol. BME-32, no. 12, pp. 1052–1060, Dec. 1985.
[28] Y. Sun, K. L. Chan, and S. M. Krishnan, “ECG signal conditioning by morphological filtering,” Comput. Biol. Med., vol. 32, no. 6, pp. 465–479, 2002.
[29] M. Gertsch, The ECG: A Two-Step Approach to Diagnosis. New York, NY, USA: Springer, 2003.
[30] P. Laguna, R. G. Mark, A. Goldberg, and G. B. Moody, “A database for evaluation of algorithms for measurement of QT and other waveform intervals in the ECG,” in Proc. Comput. Cardiol., 1997, pp. 673–676.
[31] A. L. Goldberger et al., “Physiobank, physiotoolkit, and physionet components
of a new research resource for complex physiologic signals,” Circulation, vol. 101, no. 23, pp. e215–e220, 2000.
[32] I. Silva and G. Moody, “An open-source toolbox for analysing and processing PhysioNet databases inMATLAB and Octave,” J. Open Res. Soft., vol. 2, no. 1, p. e27, 2014.
[33] Testing and Reporting Performance Results of Cardiac Rhythm and ST Segment Measurement Algorithms. NSI/AAMI EC57:1998/(R)2008 (Revision of AAMI ECAR:1987), Association for the Advancement of Medical Instrumentation, 1999.
[34] L.Y. Di Marco and L. Chiari, “Awavelet-basedECGdelineation algorithm for 32-bit integer online processing,” Biomed. Eng. Online, vol. 10, no. 1, 2011, Art. no. 23.
[35] The CSE Working Party, “Recommendations for measurement standards in quantitative electrocardiography,” Eur. Heart J., vol. 6, no. 10, pp. 815–825, 1985.
[36] S. Mehta and N. Lingayat, “Detection of P and T-waves in electrocardiogram,”
in Proc. World Congr. Eng. Comput. Sci., 2008, pp. 22–24.
[37] Texas Instruments, ADS1292, 2017. [Online]. Available: http://www.ti.com/product/ADS1292
[38] G. B. Moody and R. G. Mark, “The impact of the MIT-BIH arrhythmia database,” IEEE Eng. Med. Biol.Mag., vol. 20, no. 3, pp. 45–50, May/Jun. 2001.
[39] G. B. Moody and R. G. Mark, “The MIT-BIH Arrhythmia Database on CD-ROM and software for use with it,” in Proc. Comput. Cardiol., 1990, pp. 185–188.
[40] E. B. Mazomenos et al., “A low-complexity ECG feature extraction algorithm for mobile healthcare applications,” IEEE J. Biomed. Health Informat., vol. 17, no. 2, pp. 459–469, Mar. 2013.
[41] Shimmer, 2017. [Online]. Available: http://www.shimmersensing.com
[42] J. Eriksson, A. Dunkels, N. Finne, F. Osterlind, and T. Voigt, “Mspsim- an extensible simulator for msp430-equipped sensor boards,” in Proc. Eur. Conf. Wireless Sens. Netw., 2007, p. 27.
[43] R. Yates, Practical Considerations in Fixed-Point FIR Filter Implementations,Digital Signal Labs, Technical Reference, Mar. 2010.
[44] IP LogiCORE FIR Compiler v5.0, Xilinx, San Jose, CA, USA, 2011.



# 心得
{%pdf https://drive.google.com/uc?id=17yLVkUaywikpec2H2UYVZNEPUzVOsBlg %}

<iframe 
src="https://drive.google.com/file/d/17yLVkUaywikpec2H2UYVZNEPUzVOsBlg/preview" width="640" height="480"></iframe>
